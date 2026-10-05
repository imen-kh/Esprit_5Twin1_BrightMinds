<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255', 'unique:labels,nom'],
            'organisme' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'duree_validite_mois' => ['required', 'integer', 'min:1', 'max:120'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
