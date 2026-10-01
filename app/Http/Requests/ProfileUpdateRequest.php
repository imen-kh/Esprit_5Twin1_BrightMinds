<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Chaque utilisateur connecté peut modifier son propre profil.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles communes, complétées selon le rôle de l'utilisateur connecté.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'name' => ['required', 'min:3'],
            // L'email doit rester unique, en ignorant le compte courant
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            // Numéro tunisien : 8 chiffres commençant par 2, 3, 4, 5, 7 ou 9
            'telephone' => ['nullable', 'regex:/^[234579]\d{7}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];

        if ($user->isActeur()) {
            $rules += [
                'type_acteur' => ['required', 'in:producteur,transformateur,distributeur'],
                'nom_entreprise' => ['required', 'min:2'],
                'adresse' => ['required'],
                'ville' => ['required'],
                'matricule_fiscal' => ['nullable'],
                'description' => ['nullable', 'max:1000'],
                'site_web' => ['nullable', 'url'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ];
        } elseif ($user->role === 'consommateur') {
            $rules += [
                'ville' => ['nullable'],
                'preferences' => ['nullable', 'array'],
                'preferences.*' => ['in:bio,local,equitable'],
                'allergies' => ['nullable', 'max:255'],
            ];
        }

        return $rules;
    }

    /**
     * Messages d'erreur en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins :min caractères.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'telephone.regex' => 'Le téléphone doit être un numéro tunisien valide de 8 chiffres.',
            'avatar.image' => "L'avatar doit être une image.",
            'avatar.mimes' => "L'avatar doit être au format JPG ou PNG.",
            'avatar.max' => "L'avatar ne doit pas dépasser 2 Mo.",
            'avatar.uploaded' => "L'envoi de l'avatar a échoué (fichier trop volumineux ?).",

            'type_acteur.required' => "Veuillez préciser votre type d'acteur.",
            'type_acteur.in' => "Le type d'acteur choisi n'est pas valide.",
            'nom_entreprise.required' => "Le nom de l'entreprise est obligatoire.",
            'nom_entreprise.min' => "Le nom de l'entreprise doit contenir au moins :min caractères.",
            'adresse.required' => "L'adresse est obligatoire.",
            'ville.required' => 'La ville est obligatoire.',
            'description.max' => 'La description ne doit pas dépasser :max caractères.',
            'site_web.url' => 'Le site web doit être une URL valide (ex. https://exemple.tn).',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être comprise entre :min et :max.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être comprise entre :min et :max.',

            'preferences.array' => 'Les préférences ne sont pas valides.',
            'preferences.*.in' => "L'une des préférences choisies n'est pas valide.",
            'allergies.max' => 'Les allergies ne doivent pas dépasser :max caractères.',
        ];
    }
}
