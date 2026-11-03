<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'integer', 'between:1,5'],
            'titre' => ['nullable', 'string', 'max:120'],
            'commentaire' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Choisissez une note de 1 à 5 étoiles.',
            'note.between' => 'La note doit être comprise entre 1 et 5.',
            'commentaire.required' => 'Votre commentaire est obligatoire.',
            'commentaire.min' => 'Votre commentaire doit contenir au moins :min caractères.',
            'commentaire.max' => 'Votre commentaire ne peut pas dépasser :max caractères.',
        ];
    }
}
