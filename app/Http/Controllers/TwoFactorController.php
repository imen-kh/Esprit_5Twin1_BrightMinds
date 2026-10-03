<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorCodeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

/**
 * Double authentification : après le mot de passe, l'utilisateur saisit un code reçu par email.
 * Tant que le code n'est pas validé, l'utilisateur n'est PAS connecté : seul son identifiant
 * est gardé en session (clé « two_factor »), déposé par AuthController@login.
 */
class TwoFactorController extends Controller
{
    /** Nombre de codes erronés tolérés avant de devoir se reconnecter. */
    private const TENTATIVES_MAX = 5;

    /** Délai minimal, en secondes, entre deux envois de code. */
    private const DELAI_RENVOI = 60;

    /**
     * Affiche le formulaire de saisie du code.
     */
    public function showChallenge(Request $request): View|RedirectResponse
    {
        if (! $this->utilisateurEnAttente($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /**
     * Vérifie le code et connecte l'utilisateur.
     */
    public function verify(TwoFactorCodeRequest $request): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->codeDoubleAuthValide($request->validated('code'))) {
            $seSouvenir = (bool) $request->session()->get('two_factor.remember', false);

            // Le code ne sert qu'une fois
            $user->effacerCodeDoubleAuth();
            $request->session()->forget('two_factor');

            Auth::login($user, $seSouvenir);
            $request->session()->regenerate();

            return $user->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('home');
        }

        // Trop de codes erronés : on annule la tentative de connexion
        $tentatives = $request->session()->get('two_factor.tentatives', 0) + 1;

        if ($tentatives >= self::TENTATIVES_MAX) {
            $user->effacerCodeDoubleAuth();
            $request->session()->forget('two_factor');

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Trop de codes incorrects. Veuillez vous reconnecter.']);
        }

        $request->session()->put('two_factor.tentatives', $tentatives);

        $message = $user->codeDoubleAuthExpire()
            ? 'Ce code a expiré. Demandez-en un nouveau.'
            : 'Le code de vérification est incorrect.';

        return back()->withErrors(['code' => $message]);
    }

    /**
     * Renvoie un nouveau code par email (au plus un par minute).
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $attente = self::DELAI_RENVOI - (time() - $request->session()->get('two_factor.envoye_a', 0));

        if ($attente > 0) {
            return back()->withErrors(['code' => "Veuillez patienter {$attente} secondes avant de demander un nouveau code."]);
        }

        try {
            $user->envoyerCodeDoubleAuth();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['code' => "L'envoi du code a échoué. Réessayez dans un instant."]);
        }

        $request->session()->put('two_factor.envoye_a', time());
        $request->session()->put('two_factor.tentatives', 0);

        return back()->with('status', 'Un nouveau code vient de vous être envoyé par email.');
    }

    /**
     * Utilisateur ayant validé son mot de passe et en attente du code, s'il existe.
     */
    private function utilisateurEnAttente(Request $request): ?User
    {
        $id = $request->session()->get('two_factor.user_id');

        return $id ? User::find($id) : null;
    }
}
