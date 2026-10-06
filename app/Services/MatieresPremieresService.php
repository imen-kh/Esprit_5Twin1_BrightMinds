<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lien entre les commandes d'un transformateur et le module Traçabilité :
 * ses matières premières (produits bruts achetés aux producteurs) et les lots qu'il peut transformer.
 */
class MatieresPremieresService
{
    /** Commandes qui ne donnent droit à aucune matière première. */
    private const STATUTS_SANS_RECEPTION = ['refusee', 'annulee'];

    /**
     * Matières premières commandées par le transformateur : une ligne par produit brut commandé.
     *
     * @return \Illuminate\Support\Collection<int, object{commande: \App\Models\Commande, produit: \App\Models\Product, quantite: int, lot: ?\App\Models\Lot}>
     */
    public function pour(User $transformateur): Collection
    {
        // Un seul jeu de requêtes : commandes, produits, producteur, catégorie et lots disponibles
        $commandes = Commande::query()
            ->where('user_id', $transformateur->id)
            ->with([
                'products' => fn ($query) => $query->where('products.user_id', '!=', $transformateur->id)
                    ->where('type_produit', '!=', 'transforme'),
                'products.user',
                'products.category',
                'products.lots' => fn ($query) => $query->where('statut', 'disponible')
                    ->orderByDesc('date_production')->orderByDesc('id'),
            ])
            ->orderByDesc('date_commande')
            ->orderByDesc('id')
            ->get();

        return $commandes->flatMap(fn (Commande $commande) => $commande->products->map(fn (Product $produit) => (object) [
            'commande' => $commande,
            'produit' => $produit,
            'quantite' => (int) $produit->pivot->quantite,
            // Repli en attendant livraisons.lot_id : dernier lot « disponible » du produit commandé
            'lot' => in_array($commande->statut, self::STATUTS_SANS_RECEPTION, true) ? null : $produit->lots->first(),
        ]))->values();
    }

    /**
     * Lots qu'un utilisateur peut choisir comme lot d'origine : tous pour l'admin ;
     * pour un transformateur, les lots des produits bruts qu'il a commandés.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Lot>
     */
    public function lotsTransformables(User $user, ?Lot $saufLot = null): Collection
    {
        return Lot::query()
            ->with('produit.user')
            ->when(! $user->isAdmin(), fn ($query) => $query->whereIn('produit_id', $this->produitsCommandes($user)))
            ->when($saufLot, fn ($query) => $query->whereKeyNot($saufLot->id))
            ->orderByDesc('date_production')
            ->get();
    }

    /**
     * Le lot peut-il servir de lot d'origine à cet utilisateur ?
     */
    public function lotAccessible(User $user, Lot $lot): bool
    {
        return $user->isAdmin() || $this->produitsCommandes($user)->contains($lot->produit_id);
    }

    /**
     * Chiffres du tableau de bord du transformateur.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $matieres  résultat de pour()
     * @return array{produits: int, lots: int, matieres_recues: int, commandes_en_attente: int}
     */
    public function chiffres(User $transformateur, Collection $matieres): array
    {
        return [
            'produits' => Product::where('user_id', $transformateur->id)->where('type_produit', 'transforme')->count(),
            'lots' => Lot::whereHas('produit', fn ($query) => $query->where('user_id', $transformateur->id))->count(),
            'matieres_recues' => $matieres->filter(fn ($ligne) => $ligne->commande->statut === 'livree')->count(),
            'commandes_en_attente' => Commande::where('user_id', $transformateur->id)->where('statut', 'en_attente')->count(),
        ];
    }

    /**
     * Dernières étapes de transformation déclarées par le transformateur.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>
     */
    public function dernieresTransformations(User $transformateur, int $nombre = 3): Collection
    {
        return EtapeTracabilite::with('lot.produit')
            ->where('user_id', $transformateur->id)
            ->where('type_etape', 'transformation')
            ->orderByDesc('date_etape')
            ->take($nombre)
            ->get();
    }

    /**
     * Identifiants des produits bruts commandés par l'utilisateur (hors commandes refusées ou annulées).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function produitsCommandes(User $user): Collection
    {
        return Product::query()
            ->where('user_id', '!=', $user->id)
            ->where('type_produit', '!=', 'transforme')
            ->whereHas('commandes', fn ($query) => $query->where('commandes.user_id', $user->id)
                ->whereNotIn('commandes.statut', self::STATUTS_SANS_RECEPTION))
            ->pluck('id');
    }
}
