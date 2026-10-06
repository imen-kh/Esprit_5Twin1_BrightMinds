<?php

namespace App\Http\Requests;

use App\Models\Reclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReclamationRequest extends FormRequest
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
            'statut' => ['sometimes', 'required', 'string', Rule::in(Reclamation::STATUTS)],
        ];
    }
}
