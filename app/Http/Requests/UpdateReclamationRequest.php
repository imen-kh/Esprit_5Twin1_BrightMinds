<?php

namespace App\Http\Requests;

use App\Models\Reclamation;
use Illuminate\Validation\Rule;

/**
 * Mêmes règles que la création ; l'administrateur peut en plus changer le statut.
 */
class UpdateReclamationRequest extends StoreReclamationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'statut' => ['sometimes', 'required', 'string', Rule::in(Reclamation::STATUTS)],
        ];
    }
}
