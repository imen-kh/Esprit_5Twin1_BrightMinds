<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email contenant le code de vérification à 6 chiffres de la double authentification.
 */
class CodeDoubleAuthentification extends Notification
{
    /**
     * @param  string  $code  Code en clair (seule sa version hachée est enregistrée en base)
     * @param  int  $duree  Durée de validité en minutes
     */
    public function __construct(public string $code, public int $duree)
    {
    }

    /**
     * Canaux d'envoi de la notification.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Contenu de l'email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification NutriTrace')
            ->view('emails.code-double-authentification', [
                'user' => $notifiable,
                'code' => $this->code,
                'duree' => $this->duree,
            ]);
    }
}
