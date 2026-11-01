<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Vérification de l'adresse email (mécanisme natif MustVerifyEmail) : tant qu'elle n'est pas
 * confirmée, les espaces protégés par le middleware « verified » renvoient vers la page d'attente.
 */
class EmailVerificationController extends Controller
{
    /**
     * Page « Vérifiez votre email », avec le bouton de renvoi du lien.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->espaceRoute());
        }

        return view('auth.verify-email');
    }

    /**
     * Lien signé reçu par email : marque l'adresse comme vérifiée (événement Verified).
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()
            ->route($request->user()->espaceRoute())
            ->with('success', 'Votre adresse email est confirmée. Bienvenue sur NutriTrace !');
    }

    /**
     * Renvoie le lien de vérification (route limitée à 6 envois par minute).
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->espaceRoute());
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'L\'envoi de l\'email a échoué. Vérifiez la configuration de la messagerie et réessayez.']);
        }

        return back()->with('status', 'Un nouveau lien de vérification vient de vous être envoyé.');
    }
}
