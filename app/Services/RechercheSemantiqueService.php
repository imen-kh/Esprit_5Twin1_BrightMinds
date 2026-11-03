<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Similarité entre une phrase et les fiches publiées.
 * Sans clé Gemini, ou si l'API ne répond pas, la recherche classique continue seule.
 */
class RechercheSemantiqueService
{
    /**
     * @param  Collection<int, Product>  $produits
     * @return array<int, float>
     */
    public function scores(Collection $produits, string $phrase): array
    {
        if (! filled(config('services.gemini.key')) || trim($phrase) === '' || $produits->isEmpty()) {
            return [];
        }

        try {
            $requete = $this->vecteur($phrase, 'RETRIEVAL_QUERY');

            if ($requete === null) {
                return [];
            }

            $scores = [];

            foreach ($this->vecteursProduits($produits) as $id => $vecteur) {
                $scores[$id] = $this->cosinus($requete, $vecteur);
            }

            return $scores;
        } catch (Throwable $e) {
            Log::warning('Recherche : similarité indisponible.', ['erreur' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  Collection<int, Product>  $produits
     * @return array<int, list<float>>
     */
    private function vecteursProduits(Collection $produits): array
    {
        $vecteurs = [];
        $manquants = [];

        foreach ($produits->sortBy('id')->take(100) as $produit) {
            $texte = trim($produit->name.' '.$produit->description.' '.$produit->origin.' '.$produit->category?->name);
            $cle = 'recherche.embed.doc.'.sha1($texte);
            $cache = Cache::get($cle);

            if (is_array($cache)) {
                $vecteurs[$produit->id] = $cache;
            } else {
                $manquants[$produit->id] = [$cle, $texte];
            }
        }

        if ($manquants === []) {
            return $vecteurs;
        }

        $reponse = $this->client()->post($this->url('batchEmbedContents'), [
            'requests' => array_map(fn (array $paire) => [
                'model' => 'models/'.config('services.gemini.embedding_model'),
                'content' => ['parts' => [['text' => $paire[1]]]],
                'taskType' => 'RETRIEVAL_DOCUMENT',
            ], array_values($manquants)),
        ]);

        $embeddings = $reponse->json('embeddings');

        if (! $reponse->successful() || ! is_array($embeddings)) {
            return $vecteurs;
        }

        foreach (array_keys($manquants) as $position => $id) {
            $valeurs = $embeddings[$position]['values'] ?? null;

            if (! is_array($valeurs)) {
                continue;
            }

            Cache::put($manquants[$id][0], $valeurs, now()->addDays(7));
            $vecteurs[$id] = $valeurs;
        }

        return $vecteurs;
    }

    /**
     * @return list<float>|null
     */
    private function vecteur(string $texte, string $tache): ?array
    {
        $cle = 'recherche.embed.'.$tache.'.'.sha1($texte);
        $cache = Cache::get($cle);

        if (is_array($cache)) {
            return $cache;
        }

        $reponse = $this->client()->post($this->url('embedContent'), [
            'content' => ['parts' => [['text' => $texte]]],
            'taskType' => $tache,
        ]);
        $valeurs = $reponse->json('embedding.values');

        if (! $reponse->successful() || ! is_array($valeurs)) {
            return null;
        }

        Cache::put($cle, $valeurs, now()->addDay());

        return $valeurs;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(8)
            ->connectTimeout(5)
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);
    }

    private function url(string $action): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/'
            .config('services.gemini.embedding_model').':'.$action;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosinus(array $a, array $b): float
    {
        $taille = min(count($a), count($b));

        if ($taille === 0) {
            return 0.0;
        }

        $produit = 0.0;
        $normeA = 0.0;
        $normeB = 0.0;

        for ($i = 0; $i < $taille; $i++) {
            $produit += $a[$i] * $b[$i];
            $normeA += $a[$i] ** 2;
            $normeB += $b[$i] ** 2;
        }

        if ($normeA == 0.0 || $normeB == 0.0) {
            return 0.0;
        }

        return $produit / (sqrt($normeA) * sqrt($normeB));
    }
}
