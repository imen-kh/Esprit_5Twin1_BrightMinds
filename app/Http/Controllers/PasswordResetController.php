<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mot de passe oublié : envoi d'un lien par email puis choix d'un nouveau mot de passe.
 * S'appuie sur le broker de mots de passe de Laravel (table password_reset_tokens).
 */
class PasswordResetController extends Controller
{
    /**
     * Affiche le formulaire « Mot de passe oublié ».
     */
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoie le lien de réinitialisation par email.
     */
    public function sendLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $statut = Password::sendResetLink($request->only('email'));

        if ($statut === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Un lien de réinitialisation vient de vous être envoyé par email.');
        }

        return back()
            ->withErrors(['email' => $this->messageErreur($statut)])
            ->onlyInput('email');
    }

    /**
     * Affiche le formulaire du nouveau mot de passe (lien reçu par email).
     */
    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Enregistre le nouveau mot de passe si le jeton est valide.
     */
    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $statut = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Mot de passe haché par le cast du modèle ; on invalide aussi « Se souvenir de moi »
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($statut === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
        }

        return back()
            ->withErrors(['email' => $this->messageErreur($statut)])
            ->onlyInput('email');
    }

    /**
     * Traduit en français les statuts d'erreur du broker de mots de passe.
     */
    private function messageErreur(string $statut): string
    {
        return match ($statut) {
            Password::INVALID_USER => "Aucun compte n'est associé à cette adresse email.",
            Password::INVALID_TOKEN => 'Ce lien de réinitialisation est invalide ou a expiré. Faites une nouvelle demande.',
            Password::RESET_THROTTLED => 'Veuillez patienter une minute avant de refaire une demande.',
            default => 'Une erreur est survenue. Veuillez réessayer.',
        };
    }
}
