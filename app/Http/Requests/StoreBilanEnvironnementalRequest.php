<?php

namespace App\Http\Requests;

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
            'nom_produit' => ['required', 'string', 'max:255'],
            'produit_id' => ['nullable', 'integer'],
            'facteur_emission_id' => ['nullable', 'exists:facteurs_emission,id'],
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
}
