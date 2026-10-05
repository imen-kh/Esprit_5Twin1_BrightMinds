<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabelRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('labels', 'nom')->ignore($this->route('label'))],
            'organisme' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'duree_validite_mois' => ['sometimes', 'required', 'integer', 'min:1', 'max:120'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
