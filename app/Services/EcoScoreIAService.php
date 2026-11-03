<?php

namespace App\Services;

use App\Models\BilanEnvironnemental;
use App\Models\FacteurEmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EcoScoreIAService
{
    /**
     * Analyse et estime l'éco-score complet avec recommandation d'alternative locale.
     * Les chiffres (CO₂, score, lettre) viennent de la formule. Le texte est rédigé
     * par ChatGPT quand OPENAI_API_KEY est renseignée, sinon par les règles locales.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function analyser(array $params, bool $avecApi = true): array
    {
        $nomProduit = (string) ($params['nom_produit'] ?? 'Produit alimentaire');
        $quantite = max(0.1, (float) ($params['quantite_produit'] ?? 1.0));
        $unite = (string) ($params['unite_mesure'] ?? 'kg');
        $distanceKm = max(0, (float) ($params['distance_km'] ?? 0));
        $modeTransport = (string) ($params['mode_transport'] ?? 'camion');
        $modeCulture = (string) ($params['mode_culture'] ?? 'plein_champ');
        $typeEmballage = (string) ($params['type_emballage'] ?? 'vrac');
        $poidsEmballageG = max(0, (float) ($params['poids_emballage_g'] ?? 0));
        $origine = trim((string) ($params['origine_geographique'] ?? 'Tunisie'));
        $consoEau = (float) ($params['consommation_eau_l'] ?? 0);

        // 1. Calcul CO2 Production
        $facteurProduction = $this->trouverFacteurProduction($modeCulture);
        $co2ProdUnitaire = $facteurProduction ? (float) $facteurProduction->valeur : match ($modeCulture) {
            'bio' => 0.420,
            'plein_champ' => 0.850,
            'serre_chauffee' => 2.450,
            'hydroponique' => 1.350,
            default => 0.900,
        };
        $co2Production = round($co2ProdUnitaire * $quantite, 3);

        // 2. Calcul CO2 Transport
        $facteurTransport = $this->trouverFacteurTransport($modeTransport);
        // Facteur en kg CO2e / tonne.km ramené au kg
        $facteurKmParKg = $facteurTransport ? ((float) $facteurTransport->valeur / 1000.0) : match ($modeTransport) {
            'avion' => 0.001850,
            'bateau' => 0.000035,
            'train' => 0.000045,
            'camion' => 0.000160,
            default => 0.000150,
        };
        $co2Transport = round($facteurKmParKg * $distanceKm * $quantite, 3);

        // 3. Calcul CO2 Emballage
        $facteurEmballage = $this->trouverFacteurEmballage($typeEmballage);
        $co2EmballageParKgMat = $facteurEmballage ? (float) $facteurEmballage->valeur : match ($typeEmballage) {
            'plastique' => 2.800,
            'verre' => 1.150,
            'carton' => 0.720,
            'vrac' => 0.000,
            default => 1.000,
        };
        $poidsEmballageKg = ($poidsEmballageG / 1000.0) * $quantite;
        $co2Emballage = round($co2EmballageParKgMat * $poidsEmballageKg, 3);

        // 4. Empreinte Carbone Totale
        $co2Total = round($co2Production + $co2Transport + $co2Emballage, 3);

        // 5. Calcul du Score Numérique (Base 100)
        $score = 100;

        // Pénalité Carbone : perte de points proportionnelle au CO2
        $penaliteCarbone = min(50, ($co2Total / max(0.5, $quantite)) * 14);
        $score -= $penaliteCarbone;

        // Bonus / Malus Mode de culture
        if ($modeCulture === 'bio') {
            $score += 15;
        } elseif ($modeCulture === 'serre_chauffee') {
            $score -= 20;
        }

        // Bonus / Malus Distance & Transport
        if ($distanceKm <= 50) {
            $score += 15; // Ultra local / hyper-proximité
        } elseif ($distanceKm <= 150) {
            $score += 8; // Régional tunisien
        } elseif ($distanceKm > 1000 || $modeTransport === 'avion') {
            $score -= 25; // Importé lointain
        } elseif ($distanceKm > 400) {
            $score -= 10;
        }

        // Bonus / Malus Emballage
        if ($typeEmballage === 'vrac') {
            $score += 10;
        } elseif ($typeEmballage === 'carton') {
            $score += 3;
        } elseif ($typeEmballage === 'plastique' && $poidsEmballageG > 25) {
            $score -= 10;
        }

        // Ajustement eau si renseigné
        if ($consoEau > 500) {
            $score -= 5;
        }

        // Borner entre 5 et 100
        $scoreFinal = (int) max(5, min(100, round($score)));

        // 6. Attribution du Grade Éco-Score
        $ecoScore = match (true) {
            $scoreFinal >= 80 => 'A',
            $scoreFinal >= 60 => 'B',
            $scoreFinal >= 40 => 'C',
            $scoreFinal >= 20 => 'D',
            default => 'E',
        };

        // 7. Suggestion IA d'Alternative Plus Locale & Recommandations
        $suggestionIA = $this->genererSuggestionAlternativeLocale(
            $nomProduit,
            $origine,
            $distanceKm,
            $modeTransport,
            $co2Total,
            $co2Transport,
            $typeEmballage,
            $modeCulture
        );

        $resultat = [
            'nom_produit' => $nomProduit,
            'quantite_produit' => $quantite,
            'unite_mesure' => $unite,
            'origine_geographique' => $origine,
            'distance_km' => $distanceKm,
            'mode_transport' => $modeTransport,
            'type_emballage' => $typeEmballage,
            'poids_emballage_g' => $poidsEmballageG,
            'mode_culture' => $modeCulture,
            'consommation_eau_l' => $consoEau,
            'co2_production' => $co2Production,
            'co2_transport' => $co2Transport,
            'co2_emballage' => $co2Emballage,
            'co2_total' => $co2Total,
            'score_numerique' => $scoreFinal,
            'eco_score' => $ecoScore,
            'alternative_locale' => $suggestionIA['alternative_locale'],
            'recommandation_ia' => $suggestionIA['recommandation_ia'],
            'reduction_co2_estimee_pourcent' => $suggestionIA['reduction_co2_pourcent'],
            'facteur_production_id' => $facteurProduction?->id,
            'facteur_transport_id' => $facteurTransport?->id,
            'facteur_emballage_id' => $facteurEmballage?->id,
            'source_ia' => 'locale',
        ];

        if ($avecApi) {
            $reutiliser = filter_var($params['_reutiliser'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $enregistre = $reutiliser ? $this->texteDejaEnregistre($nomProduit) : null;

            if ($enregistre !== null) {
                $resultat = array_merge($resultat, $enregistre);
            } elseif (filled(config('services.openai.key'))) {
                $redige = $this->redigerAvecOpenAi($resultat);
                $resultat = $redige !== null
                    ? array_merge($resultat, $redige)
                    : array_merge($resultat, ['source_ia' => 'openai_indisponible']);
                $this->memoriserTexte($nomProduit, $resultat);
            }
        }

        return $resultat;
    }

    /**
     * Demande à ChatGPT le texte d'alternative locale. Les chiffres déjà calculés
     * sont imposés au modèle. Null si la clé manque ou si l'appel échoue.
     *
     * @param  array<string, mixed>  $resultat
     * @return array<string, mixed>|null
     */
    private function redigerAvecOpenAi(array $resultat): ?array
    {
        if (! filled(config('services.openai.key'))) {
            return null;
        }

        $consigne = 'Tu es l\'assistant éco-score de NutriTrace, en Tunisie. '
            .'On te donne un bilan déjà calculé. Tu ne modifies ni le CO2 ni le score ni la lettre. '
            .'Rédige en français une alternative alimentaire locale tunisienne (Cap Bon, Sahel, Sfax, Sud ou Nord-Ouest selon le produit) '
            .'et une recommandation concrète pour baisser l\'empreinte. '
            .'Réponds uniquement en JSON avec les clés alternative_locale (une phrase), recommandation_ia (une ou deux phrases) et reduction_co2_pourcent (nombre entre 0 et 85).';

        try {
            $reponse = Http::timeout((int) config('services.openai.timeout', 20))
                ->connectTimeout(5)
                ->acceptJson()
                ->withToken((string) config('services.openai.key'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $consigne],
                        ['role' => 'user', 'content' => json_encode([
                            'produit' => $resultat['nom_produit'],
                            'origine' => $resultat['origine_geographique'],
                            'distance_km' => $resultat['distance_km'],
                            'transport' => $resultat['mode_transport'],
                            'culture' => $resultat['mode_culture'],
                            'emballage' => $resultat['type_emballage'],
                            'co2_total' => $resultat['co2_total'],
                            'eco_score' => $resultat['eco_score'],
                            'score' => $resultat['score_numerique'],
                        ], JSON_UNESCAPED_UNICODE)],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Éco-score : appel OpenAI impossible.', ['erreur' => $e->getMessage()]);

            return null;
        }

        if (! $reponse->successful()) {
            Log::warning('Éco-score : OpenAI a répondu en erreur.', [
                'statut' => $reponse->status(),
                'corps' => Str::limit($reponse->body(), 300),
            ]);

            return null;
        }

        $json = json_decode((string) $reponse->json('choices.0.message.content'), true);

        if (! is_array($json) || blank($json['alternative_locale'] ?? null)) {
            return null;
        }

        return [
            'alternative_locale' => Str::limit(trim((string) $json['alternative_locale']), 2000, ''),
            'recommandation_ia' => Str::limit(trim((string) ($json['recommandation_ia'] ?? '')), 2000, ''),
            'reduction_co2_estimee_pourcent' => max(0, min(85, (float) ($json['reduction_co2_pourcent'] ?? $resultat['reduction_co2_estimee_pourcent']))),
            'source_ia' => 'openai',
        ];
    }

    /**
     * Texte déjà connu pour ce produit : bilan enregistré, puis cache d'un appel précédent.
     *
     * @return array<string, mixed>|null
     */
    private function texteDejaEnregistre(string $nom): ?array
    {
        $nom = trim($nom);

        if ($nom === '') {
            return null;
        }

        $bilan = BilanEnvironnemental::query()
            ->whereRaw('LOWER(nom_produit) = ?', [mb_strtolower($nom)])
            ->whereNotNull('alternative_locale')
            ->where('alternative_locale', '!=', '')
            ->orderByDesc('score_numerique')
            ->first();

        if ($bilan) {
            return [
                'alternative_locale' => $bilan->alternative_locale,
                'recommandation_ia' => $bilan->recommandation_ia,
                'reduction_co2_estimee_pourcent' => $bilan->reduction_co2_estimee_pourcent,
                'source_ia' => 'bilan',
            ];
        }

        $cache = Cache::get($this->cleTexte($nom));

        return is_array($cache) ? $cache : null;
    }

    /**
     * @param  array<string, mixed>  $resultat
     */
    private function memoriserTexte(string $nom, array $resultat): void
    {
        if (blank($resultat['alternative_locale'] ?? null)) {
            return;
        }

        $duree = ($resultat['source_ia'] ?? '') === 'openai' ? now()->addDays(30) : now()->addMinutes(10);

        Cache::put($this->cleTexte($nom), [
            'alternative_locale' => $resultat['alternative_locale'],
            'recommandation_ia' => $resultat['recommandation_ia'] ?? '',
            'reduction_co2_estimee_pourcent' => $resultat['reduction_co2_estimee_pourcent'] ?? null,
            'source_ia' => $resultat['source_ia'] ?? 'locale',
        ], $duree);
    }

    private function cleTexte(string $nom): string
    {
        return 'ecoscore.texte.'.md5(mb_strtolower(trim($nom)));
    }

    /**
     * Génère l'alternative locale et les recommandations IA avec estimations de gain écologique.
     *
     * @return array{alternative_locale: string, recommandation_ia: string, reduction_co2_pourcent: float}
     */
    private function genererSuggestionAlternativeLocale(
        string $nom,
        string $origine,
        float $distance,
        string $transport,
        float $co2Total,
        float $co2Transport,
        string $emballage,
        string $modeCulture
    ): array {
        $estImporteOuLoin = ($distance > 150) || Str::contains(Str::lower($origine), [
            'import', 'espagne', 'france', 'italie', 'turquie', 'maroc', 'chine', 'grece', 'egypte', 'europe', 'etranger'
        ]);

        $alternativeTexte = '';
        $recommandation = '';
        $gainPourcent = 0.0;

        // Détection du pôle régional agricole tunisien recommandé
        $nomMinuscule = Str::lower($nom);
        $poleLocal = match (true) {
            Str::contains($nomMinuscule, ['orange', 'citron', 'agrume', 'mandarine', 'tomate', 'harissa', 'piment', 'fraise']) => [
                'region' => 'Cap Bon (Nabeul / Korba / Menzel Bouzelfa)',
                'km_moyen' => 45,
                'explication' => 'bassin maraîcher et agrumicole historique réputé pour sa fraîcheur et ses circuits courts vers le Grand Tunis.',
            ],
            Str::contains($nomMinuscule, ['huile', 'olive', 'zitoun']) => [
                'region' => 'Sahel & Sfax (Monastir / Mahdia / Sfax)',
                'km_moyen' => 110,
                'explication' => 'terroir oléicole tunisien d\'excellence avec pressage traditionnel à froid et bilan carbone minimal.',
            ],
            Str::contains($nomMinuscule, ['datte', 'deglet', 'grenade']) => [
                'region' => 'Sud tunisien (Tozeur / Gabès / Testour)',
                'km_moyen' => 220,
                'explication' => 'production tunisienne protégée avec préservation des oasis et réduction des transports aériens.',
            ],
            Str::contains($nomMinuscule, ['pomme', 'poire', 'peche', 'abricot', 'legume']) => [
                'region' => 'Nord-Ouest (Béja / Jendouba / Siliana)',
                'km_moyen' => 95,
                'explication' => 'agriculture pluviale locale avec faible besoin d\'irrigation intensive.',
            ],
            default => [
                'region' => 'Producteurs locaux certifiés de la région (rayon < 60 km)',
                'km_moyen' => 35,
                'explication' => 'approvisionnement direct en circuit court réduisant drastiquement les intermédiaires logistiques.',
            ],
        };

        if ($estImporteOuLoin) {
            // Calcul du gain carbone transport d'une alternative locale
            $nouveauTransportCo2 = round(0.000160 * $poleLocal['km_moyen'], 3);
            $nouveauTotalEstime = max(0.1, round(($co2Total - $co2Transport) + $nouveauTransportCo2, 3));
            $gain = max(10, min(85, round((($co2Total - $nouveauTotalEstime) / max(0.01, $co2Total)) * 100, 1)));
            $gainPourcent = $gain;

            $alternativeTexte = "🌱 Alternative locale recommandée : Privilégier une production issue du {$poleLocal['region']}. "
                . "Cette alternative locale ({$poleLocal['explication']}) permet de ramener la distance de transport à environ {$poleLocal['km_moyen']} km "
                . "et d'économiser environ {$gain}% de l'empreinte carbone globale du produit !";

            $recommandationsList = [];
            $recommandationsList[] = "Remplacer l'approvisionnement distant ({$origine}, {$distance} km) par un partenariat direct avec les coopératives agricoles du {$poleLocal['region']}.";
            if ($emballage === 'plastique') {
                $recommandationsList[] = "Passer à un conditionnement en vrac ou cagettes réutilisables en bois/carton pour réduire le CO2 de l'emballage de -70%.";
            }
            if ($modeCulture !== 'bio') {
                $recommandationsList[] = "Encourager la transition vers l'agriculture biologique ou agro-écologique pour supprimer les intrants chimiques azotés très émetteurs.";
            }

            $recommandation = implode(' ', $recommandationsList);
        } else {
            // Produit déjà local !
            $gainPourcent = 15.0;
            $alternativeTexte = "✅ Excellent choix local : Ce produit provient déjà d'un circuit de proximité ({$origine}, distance estimée à {$distance} km). "
                . "Pour optimiser davantage son impact, privilégiez l'achat en pleine saison et en vrac afin de conserver un Éco-score optimal (A/B).";

            $recommandation = "L'empreinte liée au transport est déjà maîtrisée grâce au circuit court. "
                . ($emballage !== 'vrac' ? "Le principal axe d'amélioration reste l'optimisation de l'emballage vers le vrac ou le verre consigné." : "Maintenez la commercialisation en vrac sans plastique à usage unique.");
        }

        return [
            'alternative_locale' => $alternativeTexte,
            'recommandation_ia' => $recommandation,
            'reduction_co2_pourcent' => $gainPourcent,
        ];
    }

    private function trouverFacteurProduction(string $mode): ?FacteurEmission
    {
        $code = match ($mode) {
            'bio' => 'PROD_AGRI_BIO',
            'serre_chauffee' => 'PROD_SERRE_GAZ',
            'hydroponique' => 'PROD_HYDROPONIQUE',
            default => 'PROD_PLEIN_CHAMP',
        };

        return FacteurEmission::query()->where('code', $code)->first()
            ?? FacteurEmission::query()->where('categorie', 'production')->first();
    }

    private function trouverFacteurTransport(string $mode): ?FacteurEmission
    {
        $code = match ($mode) {
            'avion' => 'TRSP_AVION_FRET',
            'bateau' => 'TRSP_MARITIME_CARGO',
            'train' => 'TRSP_FERROVIAIRE',
            default => 'TRSP_CAMION_DIESEL',
        };

        return FacteurEmission::query()->where('code', $code)->first()
            ?? FacteurEmission::query()->where('categorie', 'transport')->first();
    }

    private function trouverFacteurEmballage(string $type): ?FacteurEmission
    {
        $code = match ($type) {
            'plastique' => 'EMB_PLASTIQUE_PET',
            'verre' => 'EMB_VERRE',
            'carton' => 'EMB_CARTON_RECYCLE',
            default => 'EMB_VRAC',
        };

        return FacteurEmission::query()->where('code', $code)->first()
            ?? FacteurEmission::query()->where('categorie', 'emballage')->first();
    }
}
