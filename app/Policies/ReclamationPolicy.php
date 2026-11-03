<?php

namespace App\Policies;

use App\Models\Reclamation;
use App\Models\User;

/**
 * Droits sur une réclamation, selon trois rôles :
 * client (auteur), acteur (vendeur du produit visé) et administrateur (traitement).
 */
class ReclamationPolicy
{
    public function view(User $user, Reclamation $reclamation): bool
    {
        return $reclamation->user_id === $user->id
            || $user->isAdmin()
            || $this->estVendeur($user, $reclamation);
    }

    public function create(User $user): bool
    {
        return $user->can('deposer-reclamations');
    }

    /**
     * Le client corrige sa réclamation tant qu'elle n'est pas prise en charge.
     */
    public function update(User $user, Reclamation $reclamation): bool
    {
        return $reclamation->user_id === $user->id && $reclamation->statut === 'en_attente';
    }

    public function delete(User $user, Reclamation $reclamation): bool
    {
        return $this->update($user, $reclamation);
    }

    /**
     * Fil de discussion : client, administrateur et vendeur du produit, tant que le dossier est ouvert.
     */
    public function repondre(User $user, Reclamation $reclamation): bool
    {
        return ! $reclamation->estCloturee() && $this->view($user, $reclamation);
    }

    /**
     * Back office : statut, priorité, copilote IA, analyse de responsabilité.
     */
    public function traiter(User $user): bool
    {
        return $user->isAdmin() && $user->can('traiter-reclamations');
    }

    /**
     * Le client indique lui-même que son problème est réglé.
     */
    public function cloturer(User $user, Reclamation $reclamation): bool
    {
        return $reclamation->user_id === $user->id && ! $reclamation->estCloturee();
    }

    public function evaluer(User $user, Reclamation $reclamation): bool
    {
        return $reclamation->user_id === $user->id && $reclamation->peutEtreEvaluee();
    }

    private function estVendeur(User $user, Reclamation $reclamation): bool
    {
        return $user->isActeur() && $reclamation->produit?->user_id === $user->id;
    }
}
