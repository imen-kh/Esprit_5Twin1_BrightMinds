<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacteurEmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('facteurs_emission') ?? $this->route('facteur');
        if (is_object($id)) {
            $id = $id->id;
        }

        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', Rule::unique('facteurs_emission', 'code')->ignore($id)],
            'categorie' => ['required', 'string', Rule::in(['transport', 'production', 'emballage', 'energie', 'eau', 'autre'])],
            'valeur' => ['required', 'numeric', 'min:0'],
            'unite' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'actif' => $this->boolean('actif'),
        ]);
    }
}
