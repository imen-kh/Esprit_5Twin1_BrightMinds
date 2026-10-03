<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\TwoFactorToggleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Profil de l'utilisateur connecté : chacun ne modifie que SON propre compte.
 * Les routes sont protégées par le middleware « auth ».
 */
class ProfileController extends Controller
{
    /**
     * Affiche le profil : thème front office pour l'acteur et le consommateur, back office pour l'admin seul.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $vue = $user->isAdmin() ? 'back.profil' : 'front.profil';

        return view($vue, ['user' => $user]);
    }

    /**
     * Met à jour les informations du profil (seuls les champs validés sont enregistrés).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $donnees = $request->safe()->except('avatar');

        // Aucune case cochée : le navigateur n'envoie rien, on vide donc les préférences
        if ($user->role === 'consommateur') {
            $donnees['preferences'] = $request->validated('preferences', []);
        }

        // Nouvel avatar : on l'enregistre sur le disque « public » et on supprime l'ancien
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $donnees['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($donnees);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Change le mot de passe (haché automatiquement par le cast du modèle User).
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Active ou désactive la double authentification (mot de passe vérifié par le Form Request).
     */
    public function toggleTwoFactor(TwoFactorToggleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $activer = ! $user->two_factor_enabled;

        $user->forceFill(['two_factor_enabled' => $activer])->save();
        $user->effacerCodeDoubleAuth();

        return back()->with('success', $activer
            ? 'Double authentification activée : un code vous sera envoyé par email à chaque connexion.'
            : 'Double authentification désactivée.');
    }

    /**
     * Supprime le compte après confirmation du mot de passe (interdit pour un admin).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            abort(403, 'Un compte administrateur ne peut pas être supprimé.');
        }

        $request->validateWithBag('suppression', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Le mot de passe est obligatoire pour supprimer le compte.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        Auth::logout();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
