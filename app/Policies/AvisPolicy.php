<?php

namespace App\Policies;

use App\Models\Avis;
use App\Models\Product;
use App\Models\User;

/**
 * Droits sur les avis : le client publie, le producteur répond, l'administrateur modère.
 */
class AvisPolicy
{
    /**
     * Un avis par client et par produit, sur un produit publié dont il n'est pas le vendeur.
     * L'administrateur (qui reçoit toutes les permissions) modère mais ne note pas.
     */
    public function create(User $user, Product $produit): bool
    {
        return ! $user->isAdmin()
            && $user->can('publier-avis')
            && $produit->status === Product::PUBLISHED
            && $produit->user_id !== $user->id
            && ! Avis::query()->where('user_id', $user->id)->where('produit_id', $produit->id)->exists();
    }

    public function update(User $user, Avis $avis): bool
    {
        return $avis->user_id === $user->id;
    }

    public function delete(User $user, Avis $avis): bool
    {
        return $avis->user_id === $user->id || $this->moderer($user);
    }

    /**
     * Vote « utile » et signalement : pas sur son propre avis.
     */
    public function reagir(User $user, Avis $avis): bool
    {
        return $avis->user_id !== $user->id && $avis->statut === 'publie';
    }

    /**
     * Droit de réponse : l'acteur qui vend le produit.
     */
    public function repondre(User $user, Avis $avis): bool
    {
        return $user->can('repondre-avis') && $avis->produit?->user_id === $user->id;
    }

    public function convertir(User $user, Avis $avis): bool
    {
        return $avis->user_id === $user->id && $avis->note <= 2 && $user->can('deposer-reclamations');
    }

    public function moderer(User $user): bool
    {
        return $user->isAdmin() && $user->can('moderer-avis');
    }
}
