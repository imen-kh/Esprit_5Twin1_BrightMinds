<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Livraison;
use App\Models\User;
use App\Notifications\CommandeLivraisonNotification;

class NotificationService
{
    // ─── Commandes ───────────────────────────────────────────────────────────

    /**
     * Nouvelle commande → Client + tous les Admins.
     */
    public static function notifierNouvelleCommande(Commande $commande): void
    {
        // Client
        if ($commande->user) {
            $commande->user->notify(
                new CommandeLivraisonNotification('commande.nouvelle', $commande, 'client')
            );
        }

        // Admins
        User::where('role', 'admin')->each(function (User $admin) use ($commande) {
            $admin->notify(
                new CommandeLivraisonNotification('commande.nouvelle', $commande, 'admin')
            );
        });
    }

    /**
     * Commande confirmée → Client.
     */
    public static function notifierCommandeConfirmee(Commande $commande): void
    {
        self::notifierClient($commande, 'commande.confirmee');
    }

    /**
     * Commande refusée → Client.
     */
    public static function notifierCommandeRefusee(Commande $commande): void
    {
        self::notifierClient($commande, 'commande.refusee');
    }

    /**
     * Commande en préparation → Client.
     */
    public static function notifierCommandePreparee(Commande $commande): void
    {
        self::notifierClient($commande, 'commande.preparee');
    }

    /**
     * Commande annulée → Client + Distributeur (si assigné) + Admins.
     */
    public static function notifierCommandeAnnulee(Commande $commande): void
    {
        self::notifierClient($commande, 'commande.annulee');

        if ($commande->distributeur_id && $commande->distributeur) {
            $commande->distributeur->notify(
                new CommandeLivraisonNotification('commande.annulee', $commande, 'distributeur')
            );
        }

        User::where('role', 'admin')->each(function (User $admin) use ($commande) {
            $admin->notify(
                new CommandeLivraisonNotification('commande.annulee', $commande, 'admin')
            );
        });
    }

    // ─── Livraisons ──────────────────────────────────────────────────────────

    /**
     * Livraison créée → Client + Admins.
     */
    public static function notifierLivraisonCreee(Livraison $livraison): void
    {
        $livraison->loadMissing('commande.user');

        self::notifierClientParLivraison($livraison, 'livraison.creee');

        User::where('role', 'admin')->each(function (User $admin) use ($livraison) {
            $admin->notify(
                new CommandeLivraisonNotification('livraison.creee', $livraison, 'admin')
            );
        });
    }

    /**
     * Livraison expédiée → Client.
     */
    public static function notifierLivraisonExpediee(Livraison $livraison): void
    {
        $livraison->loadMissing('commande.user');
        self::notifierClientParLivraison($livraison, 'livraison.expediee');
    }

    /**
     * Livraison en transit → Client.
     */
    public static function notifierLivraisonEnTransit(Livraison $livraison): void
    {
        $livraison->loadMissing('commande.user');
        self::notifierClientParLivraison($livraison, 'livraison.en_transit');
    }

    /**
     * Livraison livrée → Client.
     */
    public static function notifierLivraisonLivree(Livraison $livraison): void
    {
        $livraison->loadMissing('commande.user');
        self::notifierClientParLivraison($livraison, 'livraison.livree');
    }

    /**
     * Livraison annulée → Client + Admins.
     */
    public static function notifierLivraisonAnnulee(Livraison $livraison): void
    {
        $livraison->loadMissing('commande.user');
        self::notifierClientParLivraison($livraison, 'livraison.annulee');

        User::where('role', 'admin')->each(function (User $admin) use ($livraison) {
            $admin->notify(
                new CommandeLivraisonNotification('livraison.annulee', $livraison, 'admin')
            );
        });
    }

    /**
     * Dispatch selon le nouveau statut de la livraison.
     */
    public static function notifierChangementStatutLivraison(Livraison $livraison, string $nouveauStatut): void
    {
        match ($nouveauStatut) {
            'expediee'   => self::notifierLivraisonExpediee($livraison),
            'en_transit' => self::notifierLivraisonEnTransit($livraison),
            'livree'     => self::notifierLivraisonLivree($livraison),
            'annulee'    => self::notifierLivraisonAnnulee($livraison),
            default      => null,
        };
    }

    // ─── Helpers privés ──────────────────────────────────────────────────────

    private static function notifierClient(Commande $commande, string $type): void
    {
        $commande->loadMissing('user');
        if ($commande->user) {
            $commande->user->notify(
                new CommandeLivraisonNotification($type, $commande, 'client')
            );
        }
    }

    private static function notifierClientParLivraison(Livraison $livraison, string $type): void
    {
        $client = $livraison->commande?->user;
        if ($client) {
            $client->notify(
                new CommandeLivraisonNotification($type, $livraison, 'client')
            );
        }
    }
}
