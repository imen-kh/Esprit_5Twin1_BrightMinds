<?php

namespace App\Notifications;

use App\Models\Commande;
use App\Models\Livraison;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommandeLivraisonNotification extends Notification
{
    use Queueable;

    /**
     * Types d'événements pris en charge.
     * Commandes : commande.nouvelle | commande.confirmee | commande.refusee | commande.preparee | commande.annulee
     * Livraisons : livraison.creee | livraison.en_preparation | livraison.expediee | livraison.en_transit | livraison.livree | livraison.annulee
     */
    public function __construct(
        public readonly string $type,
        public readonly Commande|Livraison $entite,
        public readonly string $roleDestinataire,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $titres = [
            'commande.nouvelle'         => 'Nouvelle commande reçue',
            'commande.confirmee'        => 'Votre commande a été confirmée',
            'commande.refusee'          => 'Votre commande a été refusée',
            'commande.preparee'         => 'Votre commande est en cours de préparation',
            'commande.annulee'          => 'Commande annulée',
            'livraison.creee'           => 'Une livraison a été créée',
            'livraison.en_preparation'  => 'Votre livraison est en préparation',
            'livraison.expediee'        => 'Votre commande a été expédiée',
            'livraison.en_transit'      => 'Votre colis est en transit',
            'livraison.livree'          => 'Votre commande a été livrée',
            'livraison.annulee'         => 'Livraison annulée',
        ];

        return [
            'type'              => $this->type,
            'titre'             => $titres[$this->type] ?? 'Notification',
            'entite_type'       => $this->entite instanceof Commande ? 'commande' : 'livraison',
            'entite_id'         => $this->entite->id,
            'url'               => $this->buildUrl(),
            'role_destinataire' => $this->roleDestinataire,
        ];
    }

    /**
     * Construit l'URL vers la ressource concernée selon le rôle du destinataire.
     */
    private function buildUrl(): string
    {
        if ($this->entite instanceof Commande) {
            return match ($this->roleDestinataire) {
                'admin'        => route('admin.commandes.show', $this->entite->id),
                'distributeur' => route('distributeur.commandes.show', $this->entite->id),
                default        => route('commandes.show', $this->entite->id),
            };
        }

        // Livraison : l'admin et le distributeur voient la page livraison
        if (in_array($this->roleDestinataire, ['admin', 'distributeur'], true)) {
            return match ($this->roleDestinataire) {
                'admin'        => route('admin.livraisons.show', $this->entite->id),
                'distributeur' => route('distributeur.livraisons.show', $this->entite->id),
            };
        }

        // Client : lien vers sa commande — on utilise commande_id directement (toujours présent en BDD)
        $commandeId = $this->entite->commande_id
            ?? $this->entite->commande?->id
            ?? optional($this->entite->load('commande')->commande)->id;

        return $commandeId
            ? route('commandes.show', $commandeId)
            : route('commandes.index');
    }
}
