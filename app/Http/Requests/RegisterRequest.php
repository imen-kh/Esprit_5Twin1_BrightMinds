<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Le formulaire d'inscription est accessible à tous les visiteurs.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation de l'inscription.
     * Le rôle admin n'est volontairement pas proposé à l'inscription.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'role' => ['required', 'in:consommateur,acteur'],
            // Obligatoire uniquement pour un acteur de la chaîne
            'type_acteur' => ['required_if:role,acteur', 'nullable', 'in:producteur,transformateur,distributeur'],
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
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins :min caractères.',
            'name.max' => 'Le nom ne doit pas dépasser :max caractères.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => "Le rôle choisi n'est pas valide.",
            'type_acteur.required_if' => "Veuillez préciser votre type d'acteur.",
            'type_acteur.in' => "Le type d'acteur choisi n'est pas valide.",
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            // Messages de la règle Password (letters / numbers)
            'password.password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'password.password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        ];
    }
}
