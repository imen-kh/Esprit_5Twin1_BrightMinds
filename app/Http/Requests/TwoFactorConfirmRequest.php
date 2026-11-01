<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Profil : premier code de l'application, qui confirme l'activation de la double authentification.
 */
class TwoFactorConfirmRequest extends FormRequest
{
    /**
     * Sac d'erreurs dédié : la page du profil contient plusieurs formulaires.
     *
     * @var string
     */
    protected $errorBag = 'deuxFacteurs';

    /**
     * Seul un utilisateur connecté ayant lancé l'activation peut la confirmer.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->doubleAuthentificationEnAttente();
    }

    /**
     * Espaces retirés (« 123 456 »).
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => preg_replace('/\s+/', '', (string) $this->input('code'))]);
    }

    /**
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
            'code.required' => 'Saisissez le code à 6 chiffres affiché dans votre application.',
            'code.digits' => 'Le code doit contenir exactement 6 chiffres.',
        ];
    }
}
