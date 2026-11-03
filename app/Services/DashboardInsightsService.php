<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Résumé français du tableau de bord.
 * Les chiffres viennent exclusivement des faits fournis : l'IA ne les calcule pas.
 */
class DashboardInsightsService
{
    public function __construct(private ProduitIAService $ia) {}

    /**
     * @param  array<string, mixed>  $faits
     * @param  list<string>  $priorites
     * @return array{source: string, texte: string, priorites: list<string>}
     */
    public function resumer(string $sujet, array $faits, array $priorites): array
    {
        $cle = 'dashboard.insights.'.md5($sujet.'|'.json_encode($faits).'|'.json_encode($priorites));

        return Cache::remember($cle, now()->addMinutes(30), function () use ($sujet, $faits, $priorites) {
            $secours = $this->texteRegles($sujet, $faits, $priorites);

            if (! $this->ia->estConfigure()) {
                return ['source' => 'regles', 'texte' => $secours, 'priorites' => $priorites];
            }

            $texte = $this->ia->rediger($this->prompt($sujet, $faits, $priorites), 8, 1);

            if ($texte === null) {
                return ['source' => 'regles', 'texte' => $secours, 'priorites' => $priorites];
            }

            return ['source' => 'ia', 'texte' => $texte, 'priorites' => $priorites];
        });
    }

    /**
     * @param  array<string, mixed>  $faits
     * @param  list<string>  $priorites
     */
    private function texteRegles(string $sujet, array $faits, array $priorites): string
    {
        $lignes = [$sujet];

        foreach ($faits as $libelle => $valeur) {
            if (is_array($valeur)) {
                continue;
            }
            $lignes[] = $libelle.' : '.$valeur.'.';
        }

        if ($priorites === []) {
            $lignes[] = 'Aucune action prioritaire pour le moment.';
        } else {
            $lignes[] = 'Actions prioritaires : '.implode(' ', $priorites);
        }

        return implode(' ', $lignes);
    }

    /**
     * @param  array<string, mixed>  $faits
     * @param  list<string>  $priorites
     */
    private function prompt(string $sujet, array $faits, array $priorites): string
    {
        $donnees = json_encode([
            'sujet' => $sujet,
            'faits' => $faits,
            'priorites' => $priorites,
        ], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
        Tu rédiges un résumé en français, de 4 à 6 phrases, pour un tableau de bord agricole tunisien.
        Utilise uniquement les faits JSON ci-dessous.
        N'invente aucun chiffre, aucune date, aucun lieu, aucune météo et aucun produit absent de ces faits.
        N'ajoute pas de recommandation qui contredirait les priorités fournies.
        {$donnees}
        PROMPT;
    }
}
