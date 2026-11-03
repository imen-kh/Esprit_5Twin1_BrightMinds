<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Suggestions et analyse du catalogue via Gemini.
 * L'IA ne change jamais le statut d'un produit : l'administrateur décide seul.
 */
class ProduitIAService
{
    public function estConfigure(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Texte libre via la configuration Gemini existante. Null si l'API est indisponible.
     */
    public function rediger(string $prompt, int $timeout = 0, int $essais = 3): ?string
    {
        $texte = $this->appeler($prompt, [], $timeout, $essais);

        return ($texte !== null && trim($texte) !== '') ? trim($texte) : null;
    }

    /**
     * Propose nom, description et catégorie à partir d'une photo. Null si l'API est indisponible.
     *
     * @param  Collection<int, Category>  $categories
     * @return array{name: string, description: string, category_id: ?int, category_name: ?string, ingredients: ?string, origin: ?string}|null
     */
    public function proposerDepuisImage(UploadedFile $image, Collection $categories): ?array
    {
        $liste = $categories
            ->map(fn (Category $categorie) => $categorie->id.' : '.$categorie->name)
            ->implode("\n");

        $prompt = <<<PROMPT
        Tu analyses la photo d'un produit alimentaire pour un catalogue tunisien.
        Réponds uniquement avec un objet JSON, sans markdown, de la forme :
        {"nom":"","description":"","categorie_id":null,"ingredients":"","origine":""}
        Règles :
        - nom : 3 à 100 caractères, en français, d'après ce qui est visible.
        - description : 10 à 1000 caractères, factuelle, sans inventer de certification.
        - categorie_id : l'identifiant d'une catégorie de la liste, ou null si aucune ne convient.
        - ingredients : texte visible sur l'étiquette, ou une chaîne vide.
        - origine : lieu visible, ou une chaîne vide.
        Catégories disponibles :
        {$liste}
        PROMPT;

        $contenu = $image->get();

        if ($contenu === '') {
            return null;
        }

        $texte = $this->appeler($prompt, [[
            'inlineData' => [
                'mimeType' => $image->getMimeType() ?: 'image/jpeg',
                'data' => base64_encode($contenu),
            ],
        ]]);

        $donnees = $this->lireJson($texte);

        if ($donnees === null) {
            return null;
        }

        $nom = $this->chaine($donnees['nom'] ?? null, 100);

        if ($nom === null || mb_strlen($nom) < 3) {
            return null;
        }

        $description = $this->chaine($donnees['description'] ?? null, 1000);

        if ($description === null || mb_strlen($description) < 10) {
            $description = Str::limit($nom.' — description à compléter par le producteur.', 1000, '');
        }

        $categorie = $this->categorieProposee($donnees['categorie_id'] ?? null, $categories);
        $ingredients = $this->chaine($donnees['ingredients'] ?? null, 1000);
        $origine = $this->chaine($donnees['origine'] ?? null, 150);

        return [
            'name' => $nom,
            'description' => $description,
            'category_id' => $categorie?->id,
            'category_name' => $categorie?->name,
            'ingredients' => ($ingredients !== null && mb_strlen($ingredients) >= 10) ? $ingredients : null,
            'origin' => ($origine !== null && mb_strlen($origine) >= 3) ? $origine : null,
        ];
    }

    /**
     * Qualité visuelle indicative d'un fruit ou légume. N'évalue ni la fraîcheur ni la sécurité alimentaire.
     *
     * @return array{evaluable: bool, score: ?int, classification: ?string, defauts: list<string>, recommandations: list<string>, avertissement: string}|null
     */
    public function evaluerQualite(UploadedFile $image): ?array
    {
        $prompt = <<<'PROMPT'
        Tu observes uniquement la photo d'un fruit ou d'un légume.
        Réponds uniquement avec un objet JSON, sans markdown :
        {"evaluable":true,"score":0,"defauts":[],"recommandations":[]}
        Règles strictes :
        - Décris seulement ce qui est visible : taches, moisissure apparente, blessure, décoloration, aspect abîmé.
        - N'invente aucun défaut. Si rien d'anormal n'est visible, defauts est un tableau vide.
        - Ne conclus jamais sur la fraîcheur, la comestibilité, la sécurité alimentaire ou l'absence de contamination.
        - score : entier de 0 à 100 sur l'aspect visible, uniquement si la photo montre clairement le produit.
        - evaluable : false si la photo est floue, trop sombre, trop éloignée, coupée, ou si ce n'est pas un fruit ou un légume. Dans ce cas score vaut null, defauts est vide, et recommandations explique comment reprendre la photo.
        - recommandations : au plus 4 conseils de tri ou de présentation, sans promesse sanitaire.
        PROMPT;

        $contenu = $image->get();

        if ($contenu === '') {
            return null;
        }

        $donnees = $this->lireJson($this->appeler($prompt, [[
            'inlineData' => [
                'mimeType' => $image->getMimeType() ?: 'image/jpeg',
                'data' => base64_encode($contenu),
            ],
        ]]));

        if ($donnees === null || ! array_key_exists('evaluable', $donnees)) {
            return null;
        }

        $avertissement = 'Indication visuelle seulement. Cette analyse ne garantit ni la fraîcheur, ni la sécurité alimentaire, ni l’absence de contamination.';

        if (! (bool) $donnees['evaluable']) {
            $recommandations = $this->lignes($donnees['recommandations'] ?? []);

            return [
                'evaluable' => false,
                'score' => null,
                'classification' => null,
                'defauts' => [],
                'recommandations' => $recommandations !== [] ? $recommandations : [
                    'Reprendre la photo en lumière du jour, nette, avec le produit entier.',
                ],
                'avertissement' => $avertissement,
            ];
        }

        if (! is_numeric($donnees['score'] ?? null)) {
            return null;
        }

        $score = max(0, min(100, (int) $donnees['score']));

        return [
            'evaluable' => true,
            'score' => $score,
            'classification' => match (true) {
                $score >= 85 => 'Excellente',
                $score >= 70 => 'Bonne',
                $score >= 50 => 'Moyenne',
                default => 'Faible',
            },
            'defauts' => $this->lignes($donnees['defauts'] ?? []),
            'recommandations' => $this->lignes($donnees['recommandations'] ?? []),
            'avertissement' => $avertissement,
        ];
    }

    /**
     * Analyse de cohérence pour l'administrateur. Ne publie jamais le produit.
     *
     * @param  Collection<int, Product>  $similaires
     * @return array{resume: string, anomalies: list<string>, corrections: list<string>, coherent: bool}|null
     */
    public function analyser(Product $product, Collection $similaires): ?array
    {
        $product->loadMissing(['category', 'user']);

        $doublons = $similaires
            ->map(fn (Product $autre) => '#'.$autre->id.' '.$autre->name.' ('.$autre->libelleStatut().')')
            ->implode("\n");

        $prompt = <<<PROMPT
        Tu aides un administrateur à relire une fiche produit. Tu ne décides pas de l'approbation.
        Réponds uniquement avec un objet JSON, sans markdown :
        {"resume":"","coherent":true,"anomalies":[],"corrections":[]}
        - resume : 1 ou 2 phrases.
        - coherent : true seulement si le nom, la description, la catégorie et l'origine se correspondent.
        - anomalies : liste courte de problèmes concrets (vide si rien à signaler).
        - corrections : suggestions de texte, sans changer le prix ni inventer une certification.
        Produit :
        nom : {$product->name}
        description : {$product->description}
        catégorie : {$product->category?->name}
        origine : {$product->origin}
        prix : {$product->price} DT
        ingrédients : {$product->ingredients}
        type : {$product->type_produit}
        Produits proches déjà détectés (ne les approuve pas) :
        {$doublons}
        PROMPT;

        $texte = $this->appeler($prompt);
        $donnees = $this->lireJson($texte);

        if ($donnees === null || ! isset($donnees['resume'])) {
            Log::warning('Catalogue IA : analyse admin illisible.', ['extrait' => Str::limit((string) $texte, 180)]);

            return null;
        }

        return [
            'resume' => $this->chaine($donnees['resume'], 500) ?? 'Analyse indisponible.',
            'anomalies' => $this->lignes($donnees['anomalies'] ?? []),
            'corrections' => $this->lignes($donnees['corrections'] ?? []),
            'coherent' => (bool) ($donnees['coherent'] ?? false),
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categorieProposee(mixed $identifiant, Collection $categories): ?Category
    {
        if (! is_numeric($identifiant)) {
            return null;
        }

        return $categories->firstWhere('id', (int) $identifiant);
    }

    /**
     * @return list<string>
     */
    private function lignes(mixed $valeur): array
    {
        return collect(is_array($valeur) ? $valeur : [])
            ->map(function ($ligne) {
                if (is_string($ligne)) {
                    return trim($ligne);
                }

                if (! is_array($ligne)) {
                    return '';
                }

                return trim(collect($ligne)
                    ->filter(fn ($valeur) => is_string($valeur) && trim($valeur) !== '')
                    ->implode(' : '));
            })
            ->filter(fn ($ligne) => $ligne !== '')
            ->take(5)
            ->values()
            ->all();
    }

    private function chaine(mixed $valeur, int $max): ?string
    {
        $texte = trim((string) $valeur);

        if ($texte === '') {
            return null;
        }

        return mb_substr($texte, 0, $max);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lireJson(?string $reponse): ?array
    {
        if ($reponse === null || trim($reponse) === '') {
            return null;
        }

        $json = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($reponse)) ?? '');

        if (! str_starts_with($json, '{') && preg_match('/\{.*\}/s', $json, $correspondance)) {
            $json = $correspondance[0];
        }

        $donnees = json_decode($json, true);

        return is_array($donnees) ? $donnees : null;
    }

    /**
     * @param  list<array<string, mixed>>  $pieces
     */
    private function appeler(string $prompt, array $pieces = [], int $timeout = 0, int $essais = 3): ?string
    {
        if (! $this->estConfigure()) {
            return null;
        }

        $delai = $timeout > 0 ? $timeout : (int) config('services.gemini.timeout', 40);
        $tentatives = max(1, $essais);

        $modeles = collect([config('services.gemini.model') ?: 'gemini-3.5-flash'])
            ->merge(config('services.gemini.secours', []))
            ->filter()
            ->unique()
            ->values();

        $parts = array_merge([['text' => $prompt]], $pieces);

        foreach ($modeles as $modele) {
            for ($essai = 1; $essai <= $tentatives; $essai++) {
                try {
                    $reponse = Http::timeout($delai)
                        ->connectTimeout(5)
                        ->acceptJson()
                        ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                        ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modele}:generateContent", [
                            'contents' => [['role' => 'user', 'parts' => $parts]],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 1024,
                            // Sans cela, Gemini 3 consomme le budget en réflexion et le JSON est coupé.
                            'thinkingConfig' => ['thinkingBudget' => 0],
                        ],
                        ]);
                } catch (Throwable $e) {
                    Log::warning('Catalogue IA : appel Gemini impossible.', ['modele' => $modele, 'essai' => $essai, 'erreur' => $e->getMessage()]);
                    $this->patienter($essai);

                    continue;
                }

                if ($reponse->successful()) {
                    $texte = collect($reponse->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

                    if (trim($texte) !== '') {
                        return trim($texte);
                    }
                }

                Log::warning('Catalogue IA : réponse Gemini inutilisable.', [
                    'modele' => $modele,
                    'essai' => $essai,
                    'statut' => $reponse->status(),
                    'message' => Str::limit((string) $reponse->json('error.message'), 180),
                ]);

                $surcharge = in_array($reponse->status(), [429, 503], true) || $reponse->serverError();

                if (! $surcharge) {
                    break 2;
                }

                $this->patienter($essai);
            }
        }

        return null;
    }

    private function patienter(int $essai): void
    {
        if ($essai >= 3 || app()->runningUnitTests()) {
            return;
        }

        usleep(700000 * $essai);
    }
}
