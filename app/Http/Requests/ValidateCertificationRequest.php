<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateCertificationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(['validee', 'refusee'])],
            'motif_refus' => ['required_if:statut,refusee', 'nullable', 'string', 'max:2000'],
        ];
    }
}
