<?php

namespace App\Http\Requests;

use App\Models\StockItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockOuvertureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockItem::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'culture' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('stock_items', 'culture_name')->where('user_id', $this->user()->id),
            ],
            'seuil_kg' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'kg_par_unite' => ['nullable', 'numeric', 'min:0.001', 'max:1000'],
            'quantite_initiale' => ['nullable', 'numeric', 'min:0.1', 'max:10000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'culture.unique' => 'Vous suivez déjà le stock de cette culture.',
            'culture.required' => 'Indiquez le produit ou la culture.',
        ];
    }
}
