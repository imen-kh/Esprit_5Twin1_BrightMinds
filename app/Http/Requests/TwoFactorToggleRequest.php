<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorToggleRequest extends FormRequest
{
    /**
     * Sac d'erreurs dédié : la page du profil contient plusieurs formulaires.
     *
     * @var string
     */
    protected $errorBag = 'deuxFacteurs';

    /**
     * Chaque utilisateur connecté gère sa propre double authentification.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Activer ou désactiver la double authentification exige le mot de passe actuel.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * Messages d'erreur en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Le mot de passe est obligatoire pour modifier ce réglage.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ];
    }
}
