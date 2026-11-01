<?php

namespace App\Http\Controllers;

use App\Http\Requests\TwoFactorConfirmRequest;
use App\Http\Requests\TwoFactorToggleRequest;
use App\Services\DoubleAuthentificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Profil : gestion de la double authentification par application (TOTP).
 * Activer -> QR code + clé manuelle -> premier code pour confirmer -> 8 codes de récupération.
 * Activer, régénérer les codes et désactiver exigent le mot de passe actuel.
 */
class DoubleAuthentificationController extends Controller
{
    public function __construct(private DoubleAuthentificationService $doubleAuth) {}

    /**
     * Génère la clé secrète : la 2FA reste inactive tant que le premier code n'est pas confirmé.
     */
    public function activer(TwoFactorToggleRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->aDoubleAuthentification()) {
            return $this->retour()->with('success', 'La double authentification est déjà active.');
        }

        $user->forceFill([
            'two_factor_secret' => $this->doubleAuth->genererSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $this->retour()->with('success', 'Scannez le QR code avec votre application, puis saisissez le code affiché pour confirmer.');
    }

    /**
     * Valide le premier code de l'application et active la 2FA ; les codes de récupération
     * sont affichés une seule fois (message flash).
     */
    public function confirmer(TwoFactorConfirmRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->doubleAuth->verifierCode($user, $request->validated('code'))) {
            return $this->retour()->withErrors(['code' => 'Code incorrect. Vérifiez l\'heure de votre téléphone et saisissez le code actuellement affiché.'], 'deuxFacteurs');
        }

        $codes = $this->doubleAuth->genererCodesRecuperation();

        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $this->retour()
            ->with('success', 'Double authentification activée. Conservez vos codes de récupération en lieu sûr.')
            ->with('codes_recuperation', $codes);
    }

    /**
     * Remplace les codes de récupération (les anciens deviennent inutilisables).
     */
    public function regenererCodes(TwoFactorToggleRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->aDoubleAuthentification(), 403, 'La double authentification n\'est pas active.');

        $codes = $this->doubleAuth->genererCodesRecuperation();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $this->retour()
            ->with('success', 'Nouveaux codes de récupération générés : les anciens ne fonctionnent plus.')
            ->with('codes_recuperation', $codes);
    }

    /**
     * Désactive la double authentification (mot de passe exigé).
     */
    public function desactiver(TwoFactorToggleRequest $request): RedirectResponse
    {
        $request->user()->desactiverDoubleAuthentification();

        return $this->retour()->with('success', 'Double authentification désactivée.');
    }

    /**
     * Abandonne une activation en cours (avant confirmation : rien n'est encore protégé).
     */
    public function annuler(Request $request): RedirectResponse
    {
        if ($request->user()->doubleAuthentificationEnAttente()) {
            $request->user()->desactiverDoubleAuthentification();
        }

        return $this->retour()->with('success', 'Activation de la double authentification annulée.');
    }

    /**
     * Retour sur la section « Double authentification » du profil.
     */
    private function retour(): RedirectResponse
    {
        return redirect()->to(route('profil.edit').'#double-authentification');
    }
}
