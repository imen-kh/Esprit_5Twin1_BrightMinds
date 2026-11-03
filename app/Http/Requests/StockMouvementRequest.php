<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiche = $this->route('stockItem');

        return $fiche !== null && ($this->user()?->can('update', $fiche) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([StockMovement::ENTREE, StockMovement::SORTIE, StockMovement::PERTE])],
            'quantite_kg' => ['required', 'numeric', 'min:0.1', 'max:10000000'],
            'motif' => ['nullable', 'string', 'max:255'],
            'moved_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'moved_at.before_or_equal' => 'La date du mouvement ne peut pas être dans le futur.',
            'quantite_kg.min' => 'La quantité doit être supérieure à zéro.',
        ];
    }
}
