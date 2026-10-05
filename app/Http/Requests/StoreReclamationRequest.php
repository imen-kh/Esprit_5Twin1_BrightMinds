<?php

namespace App\Http\Requests;

use App\Models\Reclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'produit_id' => ['required', 'integer', 'exists:products,id'],
            'commande_id' => ['nullable', 'integer', 'min:1'],
            'sujet' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priorite' => ['required', 'string', Rule::in(Reclamation::PRIORITES)],
        ];
    }

    public function messages(): array
    {
        return [
            'produit_id.required' => 'Le produit est obligatoire.',
            'produit_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'sujet.required' => 'Le sujet est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'priorite.required' => 'La priorité est obligatoire.',
        ];
    }
}
