<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('certification')) === true; }

    public function rules(): array
    {
        return [
            'label_id' => ['sometimes', 'required', 'integer', 'exists:labels,id'],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'numero' => ['sometimes', 'required', 'string', 'max:255'],
            'date_obtention' => ['sometimes', 'required', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_obtention'],
            'fichier_pdf' => ['sometimes', 'nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
