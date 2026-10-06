<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Traite la connexion.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $seSouvenir = $request->boolean('remember');

        // On vérifie d'abord les identifiants SANS connecter l'utilisateur
        if (! Auth::validate($credentials)) {
            return back()
                ->withErrors(['email' => 'Email ou mot de passe incorrect.'])
                ->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // Double authentification activée : on envoie un code par email et on attend sa saisie
        if ($user->two_factor_enabled) {
            try {
                $user->envoyerCodeDoubleAuth();
            } catch (Throwable $e) {
                report($e);

                return back()
                    ->withErrors(['email' => "L'envoi du code de vérification a échoué. Réessayez dans un instant."])
                    ->onlyInput('email');
            }

            $request->session()->put('two_factor', [
                'user_id' => $user->id,
                'remember' => $seSouvenir,
                'tentatives' => 0,
                'envoye_a' => time(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $seSouvenir);

        // Protection contre la fixation de session
        $request->session()->regenerate();

        return redirect()->route($request->user()->espaceRoute());
    }

    /**
     * Affiche le formulaire d'inscription.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Crée le compte puis connecte automatiquement l'utilisateur.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // Le mot de passe est haché automatiquement (cast « hashed » du modèle User)
        $donnees = $request->safe()->only(['name', 'email', 'role', 'type_acteur', 'password']);

        // Le type d'acteur n'a de sens que pour le rôle « acteur »
        if ($donnees['role'] !== 'acteur') {
            $donnees['type_acteur'] = null;
        }

        $user = User::create($donnees);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
