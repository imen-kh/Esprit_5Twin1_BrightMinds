<?php

namespace App\Http\Requests;

use App\Models\Livraison;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contrôle de saisie de la création d'une livraison (module Commandes et Livraisons).
 * UpdateLivraisonRequest en hérite : seule la livraison en cours d'édition change.
 */
class StoreLivraisonRequest extends FormRequest
{
    /**
     * Réservé au distributeur ; le droit sur la commande est vérifié dans le contrôleur.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isDistributeur();
    }

    /**
     * Livraison en cours d'édition (aucune à la création).
     */
    protected function livraisonCourante(): ?Livraison
    {
        return null;
    }

    /**
     * Nettoie la saisie avant validation : espaces superflus, numéro de suivi en majuscules.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'telephone' => preg_replace('/\s+/', '', (string) $this->input('telephone')),
            'code_postal' => trim((string) $this->input('code_postal')),
            'numero_suivi' => strtoupper(trim((string) $this->input('numero_suivi'))),
        ]);
    }

    /**
     * Règles de validation d'une livraison.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'adresse_livraison' => ['required', 'string', 'min:5', 'max:255'],
            // Lettres, espaces, apostrophes et tirets uniquement
            'ville' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}][\p{L} '\-]*$/u"],
            // Code postal tunisien : 4 chiffres
            'code_postal' => ['required', 'digits:4'],
            // Numéro tunisien : 8 chiffres commençant par 2, 3, 4, 5, 7 ou 9
            'telephone' => ['required', 'regex:/^[234579]\d{7}$/'],
            // Lettres, chiffres et tirets, 6 à 30 caractères, unique parmi les livraisons
            'numero_suivi' => [
                'required',
                'regex:/^[A-Z0-9][A-Z0-9\-]{5,29}$/',
                Rule::unique('livraisons', 'numero_suivi')->ignore($this->livraisonCourante()?->id),
            ],
            // Une livraison se planifie entre aujourd'hui et dans 3 mois
            'date_livraison_prevue' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addMonths(3)->toDateString()],
            'commentaire' => ['nullable', 'string', 'max:1000'],
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
            'adresse_livraison.required' => "L'adresse de livraison est obligatoire.",
            'adresse_livraison.min' => "L'adresse de livraison doit contenir au moins :min caractères.",
            'adresse_livraison.max' => "L'adresse de livraison ne doit pas dépasser :max caractères.",
            'ville.required' => 'La ville est obligatoire.',
            'ville.min' => 'La ville doit contenir au moins :min caractères.',
            'ville.max' => 'La ville ne doit pas dépasser :max caractères.',
            'ville.regex' => 'La ville ne doit contenir que des lettres, des espaces, des apostrophes ou des tirets.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'code_postal.digits' => 'Le code postal doit contenir exactement :digits chiffres.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.regex' => 'Le téléphone doit être un numéro tunisien valide de 8 chiffres.',
            'numero_suivi.required' => 'Le numéro de suivi est obligatoire.',
            'numero_suivi.regex' => 'Le numéro de suivi doit contenir 6 à 30 caractères : lettres, chiffres et tirets (ex. LIV-2026-0001).',
            'numero_suivi.unique' => 'Ce numéro de suivi est déjà utilisé par une autre livraison.',
            'date_livraison_prevue.required' => 'La date de livraison prévue est obligatoire.',
            'date_livraison_prevue.date' => "La date de livraison prévue n'est pas une date valide.",
            'date_livraison_prevue.after_or_equal' => 'La date de livraison prévue ne peut pas être dans le passé.',
            'date_livraison_prevue.before_or_equal' => 'La date de livraison prévue ne peut pas dépasser 3 mois.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser :max caractères.',
        ];
    }
}
