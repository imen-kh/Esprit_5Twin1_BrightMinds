<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockReglageRequest extends FormRequest
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
            'seuil_kg' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'kg_par_unite' => ['nullable', 'numeric', 'min:0.001', 'max:1000'],
        ];
    }
}
