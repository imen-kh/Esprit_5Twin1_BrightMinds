<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Email de vérification de l'adresse, envoyé à l'inscription (en français).
 */
class VerificationAdresseEmail extends Notification
{
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
        $duree = (int) config('auth.verification.expire', 60);

        // Lien signé et temporaire : l'identifiant et le hash de l'email ne peuvent pas être falsifiés
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes($duree), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Confirmez votre adresse email NutriTrace')
            ->view('emails.verification-adresse-email', [
                'user' => $notifiable,
                'url' => $url,
                'duree' => $duree,
            ]);
    }
}
