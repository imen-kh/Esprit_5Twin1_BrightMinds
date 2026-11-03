<?php

namespace App\Services;

use App\Models\Avis;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Intelligence du module Avis, via DeepSeek :
 *  - modération avant publication (insultes, spam, hors sujet) et notes par aspect ;
 *  - détection d'un vrai problème dans un avis négatif (proposition de réclamation) ;
 *  - « Ce que les clients en pensent » : synthèse des avis d'un produit, mise en cache.
 */
class AvisIAService
{
    /** Mots qui retiennent un avis en modération, sans IA. */
    private const MOTS_INTERDITS = ['con ', 'connard', 'idiot', 'nul à chier', 'arnaque', 'escroc', 'merde', 'putain', 'salaud', 'zebi', 'nayek'];

    public function __construct(private DeepSeekService $deepseek) {}

    /**
     * @return array{decision: string, raison: ?string, sentiment: string, aspects: array<string, int>, probleme_detecte: bool, source: string}
     */
    public function analyser(int $note, ?string $titre, string $commentaire, ?Product $produit = null): array
    {
        $local = $this->analyseLocale($note, $titre, $commentaire);

        $aspects = implode(', ', array_keys(Avis::ASPECTS));
        $systeme = <<<PROMPT
        Tu modères les avis clients de NutriTrace, plateforme tunisienne de produits alimentaires locaux.
        L'avis peut être en français, arabe, anglais ou derja tunisienne.
        Réponds uniquement par un objet JSON :
        {
          "decision": "publier" | "moderer",
          "raison": null ou "raison courte en français si moderer",
          "sentiment": "negatif" | "neutre" | "positif",
          "aspects": {"aspect": note de 1 à 5} — seulement les aspects réellement évoqués parmi : {$aspects},
          "probleme_detecte": true si le client décrit un problème concret à traiter (produit abîmé, manquant, avarié, livraison ratée), sinon false
        }
        "moderer" uniquement pour : insulte, propos haineux, données personnelles (téléphone, adresse), publicité ou lien, texte sans rapport avec le produit.
        Une critique négative mais polie doit être PUBLIÉE.
        PROMPT;

        $contexte = 'Produit : '.($produit?->name ?? '?')."\nNote : {$note}/5\nTitre : ".($titre ?: '—')."\nCommentaire : {$commentaire}";
        $ia = $this->deepseek->json($systeme, $contexte);

        if (! $ia) {
            return $local;
        }

        $aspectsIa = collect(is_array($ia['aspects'] ?? null) ? $ia['aspects'] : [])
            ->only(array_keys(Avis::ASPECTS))
            ->map(fn ($valeur) => max(1, min(5, (int) $valeur)))
            ->all();

        // Les règles locales gardent le dernier mot sur les insultes évidentes
        $decision = $local['decision'] === 'moderer' ? 'moderer' : (($ia['decision'] ?? 'publier') === 'moderer' ? 'moderer' : 'publier');

        return [
            'decision' => $decision,
            'raison' => $decision === 'moderer' ? (is_string($ia['raison'] ?? null) && $ia['raison'] !== '' ? mb_substr($ia['raison'], 0, 250) : $local['raison']) : null,
            'sentiment' => in_array($ia['sentiment'] ?? null, ['negatif', 'neutre', 'positif'], true) ? $ia['sentiment'] : $local['sentiment'],
            'aspects' => $aspectsIa ?: $local['aspects'],
            'probleme_detecte' => (bool) ($ia['probleme_detecte'] ?? $local['probleme_detecte']),
            'source' => 'ia',
        ];
    }

    /**
     * Synthèse des avis publiés d'un produit. Recalculée seulement quand les avis changent.
     *
     * @return array{resume: ?string, points_forts: list<string>, points_faibles: list<string>, aspects: array<string, float>, source: string}|null
     */
    public function synthese(Product $produit): ?array
    {
        $avis = $produit->avis()->publies()->latest()->take(40)->get();

        if ($avis->count() < 2) {
            return null;
        }

        $cle = sprintf('avis:synthese:%d:%d:%s', $produit->id, $avis->count(), $avis->max('updated_at')?->timestamp);
        $aspects = $this->aspectsMoyens($avis);

        if ($enCache = Cache::get($cle)) {
            return $enCache;
        }

        $systeme = <<<'PROMPT'
        Tu résumes les avis clients d'un produit alimentaire pour sa page publique NutriTrace.
        Réponds uniquement par un objet JSON :
        {"resume": "2 phrases en français, neutres et fidèles aux avis",
         "points_forts": ["3 éléments maximum, 6 mots chacun"],
         "points_faibles": ["3 éléments maximum, 6 mots chacun — liste vide si aucun"]}
        Ne cite aucun nom de client. N'invente rien qui ne figure pas dans les avis.
        PROMPT;

        $contexte = 'Produit : '.$produit->name."\n".$avis
            ->map(fn (Avis $a) => "- {$a->note}/5 : ".mb_substr(trim(($a->titre ? $a->titre.'. ' : '').$a->commentaire), 0, 400))
            ->implode("\n");

        $ia = $this->deepseek->json($systeme, $contexte, 0.3);

        $resultat = $ia && filled($ia['resume'] ?? null)
            ? [
                'resume' => mb_substr((string) $ia['resume'], 0, 500),
                'points_forts' => $this->liste($ia['points_forts'] ?? []),
                'points_faibles' => $this->liste($ia['points_faibles'] ?? []),
                'aspects' => $aspects,
                'source' => 'ia',
            ]
            : $this->syntheseLocale($avis, $aspects);

        // Une synthèse IA est gardée une semaine ; un repli local est recalculé plus tôt
        Cache::put($cle, $resultat, $resultat['source'] === 'ia' ? now()->addWeek() : now()->addMinutes(10));

        return $resultat;
    }

    /**
     * Moyenne par aspect (1 à 5) sur les avis qui l'évoquent.
     *
     * @param  Collection<int, Avis>  $avis
     * @return array<string, float>
     */
    public function aspectsMoyens(Collection $avis): array
    {
        $moyennes = [];

        foreach (array_keys(Avis::ASPECTS) as $aspect) {
            $notes = $avis->map(fn (Avis $a) => $a->aspects[$aspect] ?? null)->filter();

            if ($notes->isNotEmpty()) {
                $moyennes[$aspect] = round($notes->avg(), 1);
            }
        }

        return $moyennes;
    }

    /**
     * @return array{decision: string, raison: ?string, sentiment: string, aspects: array<string, int>, probleme_detecte: bool, source: string}
     */
    public function analyseLocale(int $note, ?string $titre, string $commentaire): array
    {
        $texte = ' '.mb_strtolower(($titre ?? '').' '.$commentaire).' ';

        $raison = match (true) {
            $this->contient($texte, self::MOTS_INTERDITS) => 'Propos injurieux',
            (bool) preg_match('#https?://|www\.#i', $texte) => 'Contient un lien',
            (bool) preg_match('/(\+?216)?[\s.-]?\d{2}[\s.-]?\d{3}[\s.-]?\d{3}/', $texte) => 'Contient un numéro de téléphone',
            (bool) preg_match('/(.)\1{6,}/u', $texte) => 'Texte répétitif',
            default => null,
        };

        // Note par aspect : on part de la note globale, ajustée par les mots du commentaire
        $mots = [
            'gout' => ['goût', 'gout', 'saveur', 'savoureux', 'délicieux', 'delicieux', 'fade', 'amer', 'sucré'],
            'fraicheur' => ['frais', 'fraîche', 'fraiche', 'fraîcheur', 'pourri', 'moisi', 'mou', 'abîmé', 'abime', 'flétri'],
            'emballage' => ['emballage', 'carton', 'bocal', 'sachet', 'cassé', 'casse', 'fuite'],
            'prix' => ['prix', 'cher', 'abordable', 'rapport qualité'],
            'livraison' => ['livraison', 'livré', 'livre ', 'livreur', 'retard', 'rapide', 'colis'],
        ];
        $negatifs = ['pourri', 'moisi', 'mou', 'abîmé', 'abime', 'cassé', 'casse', 'fuite', 'retard', 'cher', 'fade', 'amer', 'flétri'];

        $aspects = [];
        foreach ($mots as $aspect => $liste) {
            if ($this->contient($texte, $liste)) {
                $aspects[$aspect] = $this->contient($texte, array_intersect($liste, $negatifs)) ? min($note, 2) : max($note, 3);
            }
        }

        $probleme = $note <= 2 && $this->contient($texte, ['pourri', 'moisi', 'cassé', 'casse', 'manquant', 'abîmé', 'abime', 'retard', 'jamais reçu', 'périmé', 'perime', 'malade', 'fuite']);

        return [
            'decision' => $raison ? 'moderer' : 'publier',
            'raison' => $raison,
            'sentiment' => $note >= 4 ? 'positif' : ($note <= 2 ? 'negatif' : 'neutre'),
            'aspects' => $aspects,
            'probleme_detecte' => $probleme,
            'source' => 'regles',
        ];
    }

    /**
     * @param  Collection<int, Avis>  $avis
     * @param  array<string, float>  $aspects
     * @return array{resume: ?string, points_forts: list<string>, points_faibles: list<string>, aspects: array<string, float>, source: string}
     */
    private function syntheseLocale(Collection $avis, array $aspects): array
    {
        $moyenne = round($avis->avg('note'), 1);
        $positifs = $avis->where('note', '>=', 4)->count();
        $part = (int) round(100 * $positifs / max(1, $avis->count()));

        $forts = collect($aspects)->filter(fn ($n) => $n >= 4)->sortDesc()->keys()->map(fn ($a) => Avis::ASPECTS[$a]['label'].' appréciée')->take(3)->values()->all();
        $faibles = collect($aspects)->filter(fn ($n) => $n < 3)->sort()->keys()->map(fn ($a) => Avis::ASPECTS[$a]['label'].' à améliorer')->take(3)->values()->all();

        return [
            'resume' => "Note moyenne de {$moyenne}/5 sur {$avis->count()} avis : {$part} % des clients sont satisfaits.",
            'points_forts' => $forts,
            'points_faibles' => $faibles,
            'aspects' => $aspects,
            'source' => 'regles',
        ];
    }

    /**
     * @return list<string>
     */
    private function liste(mixed $valeur): array
    {
        return collect(is_array($valeur) ? $valeur : [])
            ->filter(fn ($element) => is_string($element) && trim($element) !== '')
            ->map(fn ($element) => mb_substr(trim($element), 0, 80))
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @param  iterable<string>  $mots
     */
    private function contient(string $texte, iterable $mots): bool
    {
        foreach ($mots as $mot) {
            if (str_contains($texte, $mot)) {
                return true;
            }
        }

        return false;
    }
}
