<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Doublons et produits proches, calculés localement à partir du nom.
 * Cette détection ne dépend pas de l'IA et ne modifie jamais le statut du produit.
 */
class ProduitSimilariteService
{
    /**
     * @return Collection<int, Product>
     */
    public function trouver(Product $product): Collection
    {
        $nom = $this->normaliser($product->name);
        $tokens = array_values(array_filter(
            explode(' ', $nom),
            fn (string $token) => strlen($token) >= 4
        ));

        if ($tokens === []) {
            return collect();
        }

        $candidats = Product::query()
            ->with('user')
            ->whereKeyNot($product->id)
            ->where(function ($query) use ($tokens) {
                foreach (array_slice($tokens, 0, 2) as $token) {
                    $query->orWhere('name', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->limit(40)
            ->get();

        return $candidats
            ->filter(fn (Product $autre) => $this->sontSimilaires($product, $autre))
            ->values();
    }

    public function sontSimilaires(Product $a, Product $b): bool
    {
        $na = $this->normaliser($a->name);
        $nb = $this->normaliser($b->name);

        if ($na === '' || $nb === '') {
            return false;
        }

        if ($na === $nb) {
            return true;
        }

        $court = min(strlen($na), strlen($nb));
        $long = max(strlen($na), strlen($nb));

        if ($court < 6 || ($court / $long) < 0.72) {
            return false;
        }

        similar_text($na, $nb, $pourcent);

        return $pourcent >= 84;
    }

    public function normaliser(string $nom): string
    {
        $nom = Str::ascii(mb_strtolower(trim($nom)));
        $nom = preg_replace('/[^a-z0-9]+/', ' ', $nom) ?? '';

        return trim($nom);
    }
}
