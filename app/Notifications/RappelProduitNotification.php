<?php

namespace App\Notifications;

use App\Models\Lot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rappel d'un lot déclenché depuis une alerte qualité : prévient les clients concernés.
 */
class RappelProduitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lot $lot, public string $motif) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel de produit : '.$this->lot->produit?->name.' (lot '.$this->lot->code.')')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Par précaution, le lot '.$this->lot->code.' de '.$this->lot->produit?->name.' est rappelé.')
            ->line('Motif : '.$this->motif)
            ->line('Ne consommez pas ce produit et conservez l\'emballage.')
            ->action('Voir la fiche du lot', route('tracabilite.show', $this->lot))
            ->salutation('L\'équipe NutriTrace');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'titre' => 'Rappel de produit',
            'message' => 'Le lot '.$this->lot->code.' de '.$this->lot->produit?->name.' est rappelé : '.$this->motif,
            'reference' => $this->lot->code,
            'url' => route('tracabilite.show', $this->lot),
            'evenement' => 'rappel',
        ];
    }
}
