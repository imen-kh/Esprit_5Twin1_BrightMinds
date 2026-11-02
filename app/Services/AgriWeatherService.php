<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Météo agricole du producteur (Open-Meteo) et conseils à partir de ces mesures.
 * Aucun chiffre météo n'est inventé : sans réponse de l'API, le tableau reste vide.
 */
class AgriWeatherService
{
    public function __construct(private ProduitIAService $ia) {}

    /**
     * @return array<string, mixed>
     */
    public function tableau(User $user, ?string $villeDemandee = null): array
    {
        $villeDemandee = filled($villeDemandee) ? trim($villeDemandee) : null;

        if ($villeDemandee !== null) {
            $lieu = $this->geocoder($villeDemandee);

            if ($lieu === null) {
                return $this->indisponible('Aucune localisation trouvée pour « '.$villeDemandee.' ». Vérifiez le nom de la ville.', null, $villeDemandee);
            }
        } elseif (! $this->aUnLieu($user)) {
            return $this->indisponible('Indiquez votre ville ou vos coordonnées GPS dans votre profil pour afficher la météo.');
        } else {
            $lieu = $this->localiser($user);

            if ($lieu === null) {
                return $this->indisponible('La météo est indisponible pour le moment. Réessayez dans quelques minutes.');
            }
        }

        $meteo = $this->previsions($lieu['latitude'], $lieu['longitude']);

        if ($meteo === null) {
            return $this->indisponible('La météo est indisponible pour le moment. Réessayez dans quelques minutes.', $lieu, $villeDemandee);
        }

        $cultures = $this->cultures($user);
        $seuils = $this->seuilsPour($cultures);
        $alertes = $this->alertes($meteo, $seuils);
        $conseils = $this->conseils($lieu, $meteo, $cultures, $alertes);

        return [
            'disponible' => true,
            'message' => null,
            'lieu' => $lieu,
            'meteo' => $meteo,
            'cultures' => $cultures,
            'seuils' => $seuils,
            'alertes' => $alertes,
            'conseils' => $conseils['textes'],
            'conseils_source' => $conseils['source'],
            'ville_demandee' => $villeDemandee,
        ];
    }

    /**
     * @return array{nom: string, latitude: float, longitude: float}|null
     */
    public function aUnLieu(User $user): bool
    {
        return (is_numeric($user->latitude) && is_numeric($user->longitude)) || filled($user->ville);
    }

    public function localiser(User $user): ?array
    {
        if (is_numeric($user->latitude) && is_numeric($user->longitude)) {
            return [
                'nom' => filled($user->ville) ? (string) $user->ville : 'Position enregistrée',
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
            ];
        }

        if (! filled($user->ville)) {
            return null;
        }

        return $this->geocoder((string) $user->ville);
    }

    /**
     * @return array{actuelle: array<string, mixed>, jours: list<array<string, mixed>>}|null
     */
    public function previsions(float $latitude, float $longitude): ?array
    {
        $cle = sprintf('agriweather.%s.%s', round($latitude, 2), round($longitude, 2));

        $enCache = Cache::get($cle);

        if (is_array($enCache)) {
            return $enCache;
        }

        $meteo = $this->telecharger($latitude, $longitude);

        if ($meteo !== null) {
            Cache::put($cle, $meteo, now()->addMinutes((int) config('services.openmeteo.cache_minutes', 30)));
        }

        return $meteo;
    }

    /**
     * @param  list<string>  $cultures
     * @return array{pluie_mm: float, chaleur_c: float, gel_c: float, vent_kmh: float}
     */
    public function seuilsPour(array $cultures): array
    {
        $seuils = config('agriweather.seuils.defaut');

        foreach ($cultures as $culture) {
            $nom = Str::ascii(mb_strtolower($culture));

            foreach (config('agriweather.seuils') as $cle => $valeurs) {
                if ($cle === 'defaut' || ! str_contains($nom, $cle) || ! is_array($valeurs)) {
                    continue;
                }

                foreach ($valeurs as $regle => $valeur) {
                    $seuils[$regle] = match ($regle) {
                        'gel_c' => max((float) $seuils[$regle], (float) $valeur),
                        default => min((float) $seuils[$regle], (float) $valeur),
                    };
                }
            }
        }

        return $seuils;
    }

    /**
     * @param  array{actuelle: array<string, mixed>, jours: list<array<string, mixed>>}  $meteo
     * @param  array{pluie_mm: float, chaleur_c: float, gel_c: float, vent_kmh: float}  $seuils
     * @return list<array{type: string, niveau: string, titre: string, detail: string, recommandation: string, classe: string}>
     */
    public function alertes(array $meteo, array $seuils): array
    {
        $trouvees = [];

        foreach ($meteo['jours'] as $jour) {
            $date = $this->formaterDate((string) $jour['date']);

            if ((float) $jour['pluie'] >= (float) $seuils['pluie_mm']) {
                $trouvees['pluie'] = $this->alerte('pluie', 'eleve', 'Risque de fortes pluies', $jour['pluie'].' mm prévus le '.$date, 'Vérifiez le sol et les conditions avant de récolter.');
            } elseif ((float) $jour['pluie'] >= (float) $seuils['pluie_mm'] / 2 && ! isset($trouvees['pluie'])) {
                $trouvees['pluie'] = $this->alerte('pluie', 'moyen', 'Pluies à surveiller', $jour['pluie'].' mm prévus le '.$date, 'Prévoyez un créneau de récolte entre les averses.');
            }

            if ((float) $jour['max'] >= (float) $seuils['chaleur_c']) {
                $trouvees['chaleur'] = $this->alerte('chaleur', 'eleve', 'Température élevée', $jour['max'].' °C attendus le '.$date, 'Récoltez tôt le matin et protégez les plants sensibles.');
            } elseif ((float) $jour['max'] >= (float) $seuils['chaleur_c'] - 3 && ! isset($trouvees['chaleur'])) {
                $trouvees['chaleur'] = $this->alerte('chaleur', 'moyen', 'Chaleur modérée', $jour['max'].' °C attendus le '.$date, 'Surveillez l’arrosage en fin de journée.');
            }

            if ((float) $jour['min'] <= (float) $seuils['gel_c']) {
                $trouvees['gel'] = $this->alerte('gel', 'eleve', 'Risque de gel', $jour['min'].' °C attendus le '.$date, 'Couvrez les cultures sensibles pendant la nuit.');
            } elseif ((float) $jour['min'] <= (float) $seuils['gel_c'] + 2 && ! isset($trouvees['gel'])) {
                $trouvees['gel'] = $this->alerte('gel', 'moyen', 'Frais nocturne', $jour['min'].' °C attendus le '.$date, 'Surveillez les jeunes plants en début de matinée.');
            }

            if ((float) $jour['vent'] >= (float) $seuils['vent_kmh']) {
                $trouvees['vent'] = $this->alerte('vent', 'eleve', 'Vent violent', $jour['vent'].' km/h attendus le '.$date, 'Sécurisez les tuteurs, les serres et l’irrigation.');
            } elseif ((float) $jour['vent'] >= (float) $seuils['vent_kmh'] * 0.7 && ! isset($trouvees['vent'])) {
                $trouvees['vent'] = $this->alerte('vent', 'moyen', 'Vent soutenu', $jour['vent'].' km/h attendus le '.$date, 'Évitez les traitements foliaires pendant les rafales.');
            }
        }

        return array_values($trouvees);
    }

    /**
     * @return list<string>
     */
    private function cultures(User $user): array
    {
        return $user->products()
            ->where('type_produit', 'matiere_premiere')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{nom: string, latitude: float, longitude: float}  $lieu
     * @param  array{actuelle: array<string, mixed>, jours: list<array<string, mixed>>}  $meteo
     * @param  list<string>  $cultures
     * @param  list<array<string, mixed>>  $alertes
     * @return array{source: string, textes: list<string>}
     */
    private function conseils(array $lieu, array $meteo, array $cultures, array $alertes): array
    {
        $regles = $alertes === []
            ? ['Aucune alerte sur 7 jours pour les seuils de vos cultures. Poursuivez le suivi habituel.']
            : array_values(array_map(fn (array $alerte) => $alerte['recommandation'].' ('.$alerte['detail'].')', $alertes));

        $texte = $this->ia->rediger($this->prompt($lieu, $meteo, $cultures, $alertes), 8, 1);
        $lignes = $this->lignesConseil($texte);

        if ($lignes === []) {
            return ['source' => 'regles', 'textes' => $regles];
        }

        return ['source' => 'ia', 'textes' => $lignes];
    }

    /**
     * @param  array{nom: string, latitude: float, longitude: float}  $lieu
     * @param  array{actuelle: array<string, mixed>, jours: list<array<string, mixed>>}  $meteo
     * @param  list<string>  $cultures
     * @param  list<array<string, mixed>>  $alertes
     */
    private function prompt(array $lieu, array $meteo, array $cultures, array $alertes): string
    {
        $jours = collect($meteo['jours'])->map(function (array $jour) {
            return $jour['date'].' : min '.$jour['min'].' °C, max '.$jour['max'].' °C, pluie '.$jour['pluie'].' mm, vent '.$jour['vent'].' km/h';
        })->implode("\n");

        $nomsAlertes = collect($alertes)->pluck('titre')->implode(', ') ?: 'aucune';
        $nomsCultures = $cultures === [] ? 'non renseignées' : implode(', ', $cultures);
        $actuelle = $meteo['actuelle'];

        return <<<PROMPT
        Tu conseilles un agriculteur en Tunisie. Utilise uniquement les chiffres fournis. N'invente aucune température, pluie ou vent.
        Réponds par exactement 3 conseils courts, un par ligne, sans numérotation.
        Lieu : {$lieu['nom']}
        Cultures : {$nomsCultures}
        Maintenant : {$actuelle['temperature']} °C, humidité {$actuelle['humidite']} %, pluie {$actuelle['precipitations']} mm, vent {$actuelle['vent']} km/h
        Prévisions :
        {$jours}
        Alertes calculées : {$nomsAlertes}
        PROMPT;
    }

    /**
     * @return list<string>
     */
    private function lignesConseil(?string $texte): array
    {
        if ($texte === null) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $texte) ?: [])
            ->map(fn (string $ligne) => trim($ligne, " \t-•*0123456789."))
            ->filter(fn (string $ligne) => mb_strlen($ligne) >= 12)
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @return array{nom: string, latitude: float, longitude: float}|null
     */
    private function geocoder(string $ville): ?array
    {
        $cle = 'agriweather.geocode.'.Str::ascii(mb_strtolower(trim($ville)));
        $enCache = Cache::get($cle);

        if (is_array($enCache)) {
            return $enCache;
        }

        try {
            $reponse = $this->client()->retry(2, 400, throw: false)->get((string) config('services.openmeteo.geocode_url'), [
                    'name' => $ville,
                    'count' => 1,
                    'language' => 'fr',
                    'format' => 'json',
                ]);
        } catch (Throwable $e) {
            Log::warning('Météo agricole : géocodage impossible.', ['erreur' => $e->getMessage()]);

            return null;
        }

        $resultat = $reponse->json('results.0');

        if (! $reponse->successful() || ! is_array($resultat) || ! isset($resultat['latitude'], $resultat['longitude'])) {
            return null;
        }

        $lieu = [
            'nom' => (string) ($resultat['name'] ?? $ville),
            'latitude' => (float) $resultat['latitude'],
            'longitude' => (float) $resultat['longitude'],
        ];

        Cache::put($cle, $lieu, now()->addDay());

        return $lieu;
    }

    /**
     * @return array{actuelle: array<string, mixed>, jours: list<array<string, mixed>>}|null
     */
    private function telecharger(float $latitude, float $longitude): ?array
    {
        try {
            $reponse = $this->client()->retry(2, 400, throw: false)->get((string) config('services.openmeteo.forecast_url'), [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max',
                    'timezone' => 'Africa/Tunis',
                    'forecast_days' => 7,
                    'wind_speed_unit' => 'kmh',
                ]);
        } catch (Throwable $e) {
            Log::warning('Météo agricole : prévisions injoignables.', ['erreur' => $e->getMessage()]);

            return null;
        }

        if (! $reponse->successful() || ! is_array($reponse->json('current')) || ! is_array($reponse->json('daily.time'))) {
            return null;
        }

        $actuelle = $reponse->json('current');
        $jours = [];

        foreach ($reponse->json('daily.time') as $index => $date) {
            $jours[] = [
                'date' => (string) $date,
                'min' => (float) ($reponse->json("daily.temperature_2m_min.$index") ?? 0),
                'max' => (float) ($reponse->json("daily.temperature_2m_max.$index") ?? 0),
                'pluie' => (float) ($reponse->json("daily.precipitation_sum.$index") ?? 0),
                'vent' => (float) ($reponse->json("daily.wind_speed_10m_max.$index") ?? 0),
                'code' => (int) ($reponse->json("daily.weather_code.$index") ?? 0),
                'libelle' => $this->libelle((int) ($reponse->json("daily.weather_code.$index") ?? 0)),
            ];
        }

        if ($jours === []) {
            return null;
        }

        return [
            'actuelle' => [
                'temperature' => (float) ($actuelle['temperature_2m'] ?? 0),
                'humidite' => (int) ($actuelle['relative_humidity_2m'] ?? 0),
                'precipitations' => (float) ($actuelle['precipitation'] ?? 0),
                'vent' => (float) ($actuelle['wind_speed_10m'] ?? 0),
                'code' => (int) ($actuelle['weather_code'] ?? 0),
                'libelle' => $this->libelle((int) ($actuelle['weather_code'] ?? 0)),
            ],
            'jours' => $jours,
        ];
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(12)
            ->connectTimeout(8)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'NutriTrace/1.0 (contact@nutritrace.tn)'])
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ]);
    }

    private function libelle(int $code): string
    {
        return match (true) {
            $code === 0 => 'Ciel dégagé',
            $code <= 3 => 'Peu nuageux',
            $code <= 48 => 'Brouillard',
            $code <= 57 => 'Bruine',
            $code <= 67 => 'Pluie',
            $code <= 77 => 'Neige',
            $code <= 82 => 'Averses',
            $code <= 86 => 'Averses de neige',
            $code <= 99 => 'Orage',
            default => 'Conditions variables',
        };
    }

    private function formaterDate(string $date): string
    {
        $moment = strtotime($date);

        return $moment === false ? $date : date('d/m', $moment);
    }

    /**
     * @return array{type: string, niveau: string, titre: string, detail: string, recommandation: string, classe: string}
     */
    private function alerte(string $type, string $niveau, string $titre, string $detail, string $recommandation): array
    {
        return [
            'type' => $type,
            'niveau' => $niveau,
            'titre' => $titre,
            'detail' => $detail,
            'recommandation' => $recommandation,
            'classe' => $niveau === 'eleve' ? 'danger' : 'warning',
        ];
    }

    /**
     * @param  array{nom: string, latitude: float, longitude: float}|null  $lieu
     * @return array<string, mixed>
     */
    private function indisponible(string $message, ?array $lieu = null, ?string $villeDemandee = null): array
    {
        return [
            'disponible' => false,
            'message' => $message,
            'lieu' => $lieu,
            'meteo' => null,
            'cultures' => [],
            'seuils' => config('agriweather.seuils.defaut'),
            'alertes' => [],
            'conseils' => [],
            'conseils_source' => null,
            'ville_demandee' => $villeDemandee,
        ];
    }
}
