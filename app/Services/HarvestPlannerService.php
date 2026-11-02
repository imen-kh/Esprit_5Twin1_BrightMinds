<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Fenêtre indicative et risques météo d'une récolte.
 * Les kilogrammes affichés sont ceux déclarés par le producteur.
 */
class HarvestPlannerService
{
    public function __construct(
        private AgriWeatherService $meteo,
        private ProduitIAService $ia,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function fiche(Harvest $harvest): array
    {
        $harvest->loadMissing(['product', 'user']);
        $fenetre = $this->fenetre($harvest->nom(), $harvest->planted_at);
        $risques = $this->risques($harvest->user, $harvest->planned_harvest_at, $harvest->nom());
        $explication = $this->explication($harvest, $fenetre, $risques);

        return [
            'fenetre' => $fenetre,
            'risques' => $risques,
            'explication' => $explication['texte'],
            'explication_source' => $explication['source'],
        ];
    }

    /**
     * @return array{min_jours: int, max_jours: int, debut: Carbon, fin: Carbon}
     */
    public function fenetre(string $culture, Carbon $plantation): array
    {
        $cycle = config('harvest.cycles.defaut');
        $nom = Str::ascii(mb_strtolower($culture));
        $longueur = 0;

        foreach (config('harvest.cycles') as $cle => $valeurs) {
            if ($cle === 'defaut' || ! is_array($valeurs) || ! str_contains($nom, $cle)) {
                continue;
            }

            if (strlen($cle) > $longueur) {
                $cycle = array_merge($cycle, $valeurs);
                $longueur = strlen($cle);
            }
        }

        return [
            'min_jours' => (int) $cycle['min'],
            'max_jours' => (int) $cycle['max'],
            'debut' => $plantation->copy()->addDays((int) $cycle['min']),
            'fin' => $plantation->copy()->addDays((int) $cycle['max']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function risques(User $user, Carbon $date, string $culture): array
    {
        if (! $this->meteo->aUnLieu($user)) {
            return $this->sansMeteo('Indiquez votre ville ou vos coordonnées GPS dans votre profil pour évaluer les risques.');
        }

        $lieu = $this->meteo->localiser($user);

        if ($lieu === null) {
            return $this->sansMeteo('La météo est indisponible pour le moment.');
        }

        $previsions = $this->meteo->previsions($lieu['latitude'], $lieu['longitude']);

        if ($previsions === null) {
            return $this->sansMeteo('La météo est indisponible pour le moment.');
        }

        $jour = collect($previsions['jours'])->firstWhere('date', $date->toDateString());

        if (! is_array($jour)) {
            return [
                'disponible' => true,
                'hors_horizon' => true,
                'message' => 'Open-Meteo couvre 7 jours. Aucun risque n’est calculé pour une récolte plus lointaine.',
                'alertes' => [],
                'jour' => null,
                'lieu' => $lieu,
            ];
        }

        return [
            'disponible' => true,
            'hors_horizon' => false,
            'message' => null,
            'alertes' => $this->meteo->alertes(
                ['jours' => [$jour]],
                $this->meteo->seuilsPour([$culture]),
            ),
            'jour' => $jour,
            'lieu' => $lieu,
        ];
    }

    /**
     * @param  array{debut: Carbon, fin: Carbon, min_jours: int, max_jours: int}  $fenetre
     * @param  array<string, mixed>  $risques
     * @return array{source: string, texte: string}
     */
    private function explication(Harvest $harvest, array $fenetre, array $risques): array
    {
        $regle = $this->texteRegle($harvest, $fenetre, $risques);
        $ia = $this->ia->rediger($this->prompt($harvest, $fenetre, $risques), 8, 1);

        if ($ia === null) {
            return ['source' => 'regles', 'texte' => $regle];
        }

        return ['source' => 'ia', 'texte' => $ia];
    }

    /**
     * @param  array{debut: Carbon, fin: Carbon, min_jours: int, max_jours: int}  $fenetre
     * @param  array<string, mixed>  $risques
     */
    private function prompt(Harvest $harvest, array $fenetre, array $risques): string
    {
        $alertes = collect($risques['alertes'])->pluck('titre')->implode(', ') ?: 'aucune';
        $meteo = is_array($risques['jour'] ?? null)
            ? 'min '.$risques['jour']['min'].' °C, max '.$risques['jour']['max'].' °C, pluie '.$risques['jour']['pluie'].' mm, vent '.$risques['jour']['vent'].' km/h'
            : 'non disponible';

        $estime = Harvest::formaterKg($harvest->estimated_kg);
        $recolte = $harvest->harvested_kg === null ? 'non renseignée' : Harvest::formaterKg($harvest->harvested_kg).' kg';

        return <<<PROMPT
        Tu expliques une récolte à un agriculteur. N'invente aucun rendement, aucun kilogramme et aucune date.
        Utilise uniquement les éléments ci-dessous. Trois phrases maximum, en français.
        Produit : {$harvest->nom()}
        Plantation déclarée : {$harvest->planted_at->format('d/m/Y')}
        Fenêtre indicative calculée par l'application : du {$fenetre['debut']->format('d/m/Y')} au {$fenetre['fin']->format('d/m/Y')}. Ce n'est pas une date garantie.
        Date de récolte choisie par le producteur : {$harvest->planned_harvest_at->format('d/m/Y')}
        Quantité estimée déclarée : {$estime} kg
        Quantité réellement récoltée : {$recolte}
        Superficie déclarée : {$harvest->area_hectares} ha
        Météo du jour choisi : {$meteo}
        Alertes déjà calculées : {$alertes}
        Limite météo : {$risques['message']}
        PROMPT;
    }

    /**
     * @param  array{debut: Carbon, fin: Carbon, min_jours: int, max_jours: int}  $fenetre
     * @param  array<string, mixed>  $risques
     */
    private function texteRegle(Harvest $harvest, array $fenetre, array $risques): string
    {
        $texte = 'Fenêtre indicative du '.$fenetre['debut']->format('d/m/Y')
            .' au '.$fenetre['fin']->format('d/m/Y')
            .' pour '.$harvest->nom()
            .', d’après un cycle de '.$fenetre['min_jours'].' à '.$fenetre['max_jours'].' jours. Ce n’est pas une date garantie. '
            .'Vous avez déclaré '.Harvest::formaterKg($harvest->estimated_kg).' kg sur '.$harvest->area_hectares
            .' ha, avec une récolte prévue le '.$harvest->planned_harvest_at->format('d/m/Y').'.';

        if ($harvest->harvested_kg !== null) {
            $texte .= ' Quantité réellement récoltée : '.Harvest::formaterKg($harvest->harvested_kg).' kg.';
        }

        foreach ($risques['alertes'] as $alerte) {
            $texte .= ' '.$alerte['recommandation'].' ('.$alerte['detail'].')';
        }

        if (filled($risques['message'])) {
            $texte .= ' '.$risques['message'];
        }

        return $texte;
    }

    /**
     * @return array<string, mixed>
     */
    private function sansMeteo(string $message): array
    {
        return [
            'disponible' => false,
            'hors_horizon' => false,
            'message' => $message,
            'alertes' => [],
            'jour' => null,
            'lieu' => null,
        ];
    }
}
