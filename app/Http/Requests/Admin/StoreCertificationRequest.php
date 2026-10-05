<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'label_id' => ['required', 'integer', 'exists:labels,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'acteur_id' => ['required', 'integer', 'exists:users,id'],
            'numero' => ['required', 'string', 'max:255'],
            'date_obtention' => ['required', 'date'],
            'date_expiration' => ['required', 'date', 'after_or_equal:date_obtention'],
            'statut' => ['required', 'in:en_attente,validee,refusee,expiree'],
            'motif_refus' => ['nullable', 'string', 'required_if:statut,refusee'],
            'fichier_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
