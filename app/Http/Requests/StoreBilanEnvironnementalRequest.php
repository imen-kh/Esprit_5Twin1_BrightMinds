<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBilanEnvironnementalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_produit' => ['nullable', 'string', 'max:255'],
            'produit_id' => ['required', 'integer', 'exists:products,id'],
            'facteur_emission_id' => ['required', 'integer', 'exists:facteurs_emission,id'],
            'facteur_production_id' => ['nullable', 'exists:facteurs_emission,id'],
            'facteur_transport_id' => ['nullable', 'exists:facteurs_emission,id'],
            'facteur_emballage_id' => ['nullable', 'exists:facteurs_emission,id'],
            'quantite_produit' => ['required', 'numeric', 'min:0.01'],
            'unite_mesure' => ['required', 'string', 'max:50'],
            'origine_geographique' => ['nullable', 'string', 'max:255'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'mode_transport' => ['nullable', 'string', 'max:100'],
            'type_emballage' => ['nullable', 'string', 'max:100'],
            'poids_emballage_g' => ['nullable', 'numeric', 'min:0'],
            'mode_culture' => ['nullable', 'string', 'max:100'],
            'consommation_eau_l' => ['nullable', 'numeric', 'min:0'],
            'co2_production' => ['nullable', 'numeric', 'min:0'],
            'co2_transport' => ['nullable', 'numeric', 'min:0'],
            'co2_emballage' => ['nullable', 'numeric', 'min:0'],
            'co2_total' => ['nullable', 'numeric', 'min:0'],
            'score_numerique' => ['nullable', 'integer', 'between:0,100'],
            'eco_score' => ['nullable', 'string', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'alternative_locale' => ['nullable', 'string'],
            'recommandation_ia' => ['nullable', 'string'],
            'reduction_co2_estimee_pourcent' => ['nullable', 'numeric'],
            'statut' => ['required', 'string', Rule::in(['valide', 'en_attente', 'archive'])],
            'commentaires' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'produit_id.required' => 'Le produit est obligatoire.',
            'produit_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'facteur_emission_id.required' => 'Le facteur d\'émission est obligatoire.',
            'facteur_emission_id.exists' => 'Le facteur d\'émission sélectionné n\'existe pas.',
            'quantite_produit.required' => 'La quantité est obligatoire.',
            'quantite_produit.min' => 'La quantité doit être supérieure à 0.',
            'statut.required' => 'Le statut est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('nom_produit') && $this->filled('produit_id')) {
            $name = Product::query()->whereKey($this->input('produit_id'))->value('name');

            if (is_string($name) && $name !== '') {
                $this->merge(['nom_produit' => $name]);
            }
        }
    }
}
