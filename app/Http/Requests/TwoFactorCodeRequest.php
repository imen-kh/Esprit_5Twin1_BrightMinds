<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Connexion en deux étapes : code à 6 chiffres de l'application OU code de récupération.
 */
class TwoFactorCodeRequest extends FormRequest
{
    /**
     * La saisie du code est accessible au visiteur qui vient de valider son mot de passe.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Espaces retirés (« 123 456 » ou « abcde-fghij » collés depuis un fichier).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? preg_replace('/\s+/', '', (string) $this->input('code')) : null,
            'recovery_code' => $this->filled('recovery_code') ? strtoupper(trim((string) $this->input('recovery_code'))) : null,
        ]);
    }

    /**
     * Règles de validation : l'un des deux codes est obligatoire.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'required_without:recovery_code', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'regex:/^[A-Z0-9]{5}-[A-Z0-9]{5}$/'],
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
            'code.required_without' => 'Saisissez le code à 6 chiffres affiché dans votre application d\'authentification.',
            'code.digits' => 'Le code doit contenir exactement 6 chiffres.',
            'recovery_code.required_without' => 'Saisissez l\'un de vos codes de récupération.',
            'recovery_code.regex' => 'Un code de récupération a la forme XXXXX-XXXXX.',
        ];
    }
}
