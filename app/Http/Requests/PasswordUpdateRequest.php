<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * Sac d'erreurs dédié : la page du profil contient plusieurs formulaires.
     *
     * @var string
     */
    protected $errorBag = 'motDePasse';

    /**
     * Chaque utilisateur connecté peut changer son propre mot de passe.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation du changement de mot de passe.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
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
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            // Messages de la règle Password (letters / numbers)
            'password.password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'password.password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        ];
    }
}
