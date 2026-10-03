<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * Règles de validation du code de vérification.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
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
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.digits' => 'Le code de vérification doit contenir :digits chiffres.',
        ];
    }
}
