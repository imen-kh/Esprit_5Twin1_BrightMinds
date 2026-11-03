<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorCodeRequest;
use App\Models\User;
use App\Services\DoubleAuthentificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Connexion en deux étapes : après le mot de passe, l'utilisateur saisit le code à 6 chiffres
 * de son application d'authentification (TOTP) ou l'un de ses codes de récupération.
 * Tant que le code n'est pas validé, l'utilisateur n'est PAS connecté : seul son identifiant
 * est gardé en session (clé « two_factor »), déposé par AuthController@login.
 */
class TwoFactorController extends Controller
{
    /** Nombre de codes erronés tolérés avant de devoir se reconnecter. */
    private const TENTATIVES_MAX = 5;

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
     * Vérifie le code (application ou récupération) et connecte l'utilisateur.
     */
    public function verify(TwoFactorCodeRequest $request, DoubleAuthentificationService $doubleAuth): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $parRecuperation = $request->filled('recovery_code');

        // Un code de récupération n'est valable qu'une fois : il est retiré de la liste dès qu'il sert
        $valide = $parRecuperation
            ? $user->utiliserCodeRecuperation($request->validated('recovery_code'))
            : $doubleAuth->verifierCode($user, $request->validated('code'));

        if ($valide) {
            $seSouvenir = (bool) $request->session()->get('two_factor.remember', false);
            $request->session()->forget('two_factor');

            Auth::login($user, $seSouvenir);
            $request->session()->regenerate();

            $redirection = redirect()->intended(route($user->espaceRoute()));

            if ($parRecuperation) {
                $restants = count($user->two_factor_recovery_codes ?? []);

                return $redirection->with('success', "Connexion avec un code de récupération : il vous en reste {$restants}. Pensez à les régénérer depuis votre profil.");
            }

            return $redirection;
        }

        // Trop de codes erronés : on annule la tentative de connexion
        $tentatives = $request->session()->get('two_factor.tentatives', 0) + 1;

        if ($tentatives >= self::TENTATIVES_MAX) {
            $request->session()->forget('two_factor');

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Trop de codes incorrects. Veuillez vous reconnecter.']);
        }

        $request->session()->put('two_factor.tentatives', $tentatives);

        return back()
            ->withInput($request->only($parRecuperation ? 'recovery_code' : []))
            ->withErrors($parRecuperation
                ? ['recovery_code' => 'Ce code de récupération est invalide ou a déjà été utilisé.']
                : ['code' => 'Code incorrect ou expiré. Saisissez le code actuellement affiché dans votre application.']);
    }

    /**
     * Utilisateur ayant validé son mot de passe et en attente du code, s'il existe.
     */
    private function utilisateurEnAttente(Request $request): ?User
    {
        $id = $request->session()->get('two_factor.user_id');
        $user = $id ? User::find($id) : null;

        return $user?->aDoubleAuthentification() ? $user : null;
    }
}
