<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Événement d'une réclamation : nouvelle (pour le vendeur), réponse ou statut (pour le client), escalade (pour l'admin).
 * La notification en base est immédiate ; l'email part par la file d'attente pour ne jamais bloquer la page.
 */
class ReclamationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reclamation $reclamation, public string $evenement) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // L'email ne concerne que le client qui a déposé la réclamation
        return $notifiable->id === $this->reclamation->user_id ? ['database', 'mail'] : ['database'];
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
            ->subject($this->titre().' — '.$this->reclamation->reference)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->message())
            ->action('Voir ma réclamation', route('reclamations.show', $this->reclamation))
            ->salutation('L\'équipe NutriTrace');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $url = match (true) {
            $notifiable->isAdmin() => route('admin.reclamations.show', $this->reclamation),
            $notifiable->id === $this->reclamation->user_id => route('reclamations.show', $this->reclamation),
            default => route('acteur.reclamations.index'),
        };

        return [
            'titre' => $this->titre(),
            'message' => $this->message(),
            'reference' => $this->reclamation->reference,
            'url' => $url,
            'evenement' => $this->evenement,
        ];
    }

    private function titre(): string
    {
        return match ($this->evenement) {
            'nouvelle' => 'Nouvelle réclamation sur votre produit',
            'reponse' => 'Nouvelle réponse à votre réclamation',
            'statut' => 'Votre réclamation a changé de statut',
            'escalade' => 'Un client demande un contact humain',
            default => 'Mise à jour de réclamation',
        };
    }

    private function message(): string
    {
        $r = $this->reclamation;

        return match ($this->evenement) {
            'nouvelle' => "« {$r->sujet} » sur {$r->produit?->name}.",
            'reponse' => "Votre réclamation « {$r->sujet} » a reçu une réponse.",
            'statut' => "Votre réclamation « {$r->sujet} » est maintenant : {$r->statutLibelle()}.",
            'escalade' => "Le dossier « {$r->sujet} » passe en priorité {$r->prioriteLibelle()}.",
            default => $r->sujet,
        };
    }
}
