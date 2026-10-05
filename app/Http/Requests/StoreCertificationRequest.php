<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isActeur() || $this->user()?->isAdmin(); }

    public function rules(): array
    {
        return [
            'label_id' => ['required', 'integer', 'exists:labels,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'numero' => ['required', 'string', 'max:255'],
            'date_obtention' => ['required', 'date'],
            'date_expiration' => ['required', 'date', 'after_or_equal:date_obtention'],
            'fichier_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
