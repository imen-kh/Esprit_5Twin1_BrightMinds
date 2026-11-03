<?php

namespace App\Http\Requests;

use App\Models\Harvest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProducteur() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'culture' => ['required', 'string', 'min:2', 'max:100'],
            'planted_at' => ['required', 'date'],
            'area_hectares' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'estimated_kg' => ['required', 'numeric', 'min:0.1', 'max:10000000'],
            'planned_harvest_at' => ['required', 'date', 'after_or_equal:planted_at'],
            'status' => ['required', Rule::in([Harvest::PLANIFIEE, Harvest::EN_COURS, Harvest::RECOLTEE])],
            'harvested_kg' => ['nullable', 'numeric', 'min:0', 'max:10000000', 'required_if:status,'.Harvest::RECOLTEE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'culture.required' => 'Indiquez le produit cultivé.',
            'culture.min' => 'Le nom de la culture doit contenir au moins :min caractères.',
            'planned_harvest_at.after_or_equal' => 'La récolte prévue ne peut pas précéder la plantation.',
            'harvested_kg.required_if' => 'Indiquez la quantité réellement récoltée.',
        ];
    }
}
