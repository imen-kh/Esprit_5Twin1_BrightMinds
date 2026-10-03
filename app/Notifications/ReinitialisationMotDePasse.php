<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email contenant le lien de réinitialisation du mot de passe (en français).
 */
class ReinitialisationMotDePasse extends Notification
{
    /**
     * @param  string  $token  Jeton en clair généré par le broker de mots de passe
     */
    public function __construct(public string $token)
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
        // Le lien porte le jeton et l'email du compte concerné
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe NutriTrace')
            ->view('emails.reinitialisation-mot-de-passe', [
                'user' => $notifiable,
                'url' => $url,
                'duree' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
