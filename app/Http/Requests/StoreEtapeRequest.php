<?php

namespace App\Http\Requests;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation de la création d'une étape de traçabilité (module Traçabilité).
 * UpdateEtapeRequest en hérite : seule la référence à l'étape en cours d'édition change.
 */
class StoreEtapeRequest extends FormRequest
{
    /**
     * Réservé aux acteurs de la chaîne et aux administrateurs.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAdmin() || $user->isActeur());
    }

    /**
     * Étape en cours d'édition (aucune à la création).
     */
    protected function etapeCourante(): ?EtapeTracabilite
    {
        return null;
    }

    /**
     * Règles de validation d'une étape.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lot_id' => ['required', 'integer', Rule::exists('lots', 'id')],
            'type_etape' => ['required', Rule::in(array_keys(EtapeTracabilite::TYPES))],
            'titre' => ['required', 'min:3', 'max:150'],
            'lieu' => ['required', 'max:150'],
            // Les coordonnées GPS vont par deux : l'une ne va pas sans l'autre
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            // Jusqu'à 150 °C : une cuisson ou une pasteurisation dépasse largement la température ambiante
            'temperature' => ['nullable', 'numeric', 'between:-30,150'],
            'description' => ['nullable', 'max:1000'],
            'date_etape' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Règles métier qui dépendent de la base : accès au lot, chronologie et type d'étape.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Inutile d'aller plus loin si les champs concernés sont déjà invalides
            if ($validator->errors()->hasAny(['lot_id', 'type_etape', 'date_etape'])) {
                return;
            }

            $user = $this->user();
            $courante = $this->etapeCourante();
            $type = $this->input('type_etape');
            $date = Carbon::parse($this->input('date_etape'));

            // 1. Le lot doit être accessible à l'utilisateur
            $lot = Lot::ouvertAuxEtapes($user)->find($this->input('lot_id'));

            if (! $lot) {
                $validator->errors()->add('lot_id', 'Vous n\'avez pas accès à ce lot.');

                return;
            }

            // 2. Un acteur ne déclare que les types d'étape de son métier (l'admin n'est pas limité)
            if (! $user->isAdmin()
                && ! in_array($type, EtapeTracabilite::TYPES_PAR_ACTEUR[$user->type_acteur] ?? [], true)) {
                $validator->errors()->add(
                    'type_etape',
                    'En tant que '.($user->type_acteur ?? 'acteur').', vous ne pouvez pas déclarer une étape de type « '
                    .EtapeTracabilite::TYPES[$type]['label'].' ».'
                );
            }

            // 3. L'étape ne peut pas précéder la production du lot
            if ($date->lt($lot->date_production->copy()->startOfDay())) {
                $validator->errors()->add(
                    'date_etape',
                    'La date de l\'étape ne peut pas être antérieure à la date de production du lot ('
                    .$lot->date_production->format('d/m/Y').').'
                );
            }

            // 4. Chronologie par rapport aux autres étapes du lot (hors étape en cours d'édition)
            $autres = $lot->etapes()->when($courante, fn ($q) => $q->whereKeyNot($courante->id))->get();
            $this->verifierChronologie($validator, $autres, $date, $courante);

            // 5. Étape « production » : une seule par lot, toujours la première, jamais sur un lot transformé
            if ($type === 'production') {
                if ($lot->lot_origine_id !== null) {
                    $validator->errors()->add('type_etape', 'Un lot issu d\'un lot d\'origine ne peut pas avoir d\'étape de production : elle figure déjà sur le lot d\'origine.');
                } elseif ($autres->contains('type_etape', 'production')) {
                    $validator->errors()->add('type_etape', 'Ce lot a déjà une étape de production.');
                } elseif ($autres->contains(fn (EtapeTracabilite $etape) => $etape->date_etape->lt($date))) {
                    $validator->errors()->add('type_etape', 'L\'étape de production doit être la première étape du lot.');
                }
            }
        });
    }

    /**
     * À la création, une étape s'ajoute à la fin : elle ne peut pas précéder la dernière étape du lot.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>  $autres  étapes du lot, triées par date
     */
    protected function verifierChronologie(Validator $validator, $autres, Carbon $date, ?EtapeTracabilite $courante): void
    {
        $derniere = $autres->last();

        if ($derniere && $date->lt($derniere->date_etape)) {
            $validator->errors()->add(
                'date_etape',
                'La date de l\'étape ne peut pas être antérieure à la dernière étape du lot ('
                .$derniere->date_etape->format('d/m/Y H:i').').'
            );
        }
    }

    /**
     * Messages d'erreur en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lot_id.required' => 'Le lot est obligatoire.',
            'lot_id.integer' => 'Le lot choisi n\'est pas valide.',
            'lot_id.exists' => 'Le lot choisi n\'existe pas.',
            'type_etape.required' => 'Le type d\'étape est obligatoire.',
            'type_etape.in' => 'Le type d\'étape choisi n\'est pas valide.',
            'titre.required' => 'Le titre est obligatoire.',
            'titre.min' => 'Le titre doit contenir au moins :min caractères.',
            'titre.max' => 'Le titre ne doit pas dépasser :max caractères.',
            'lieu.required' => 'Le lieu est obligatoire.',
            'lieu.max' => 'Le lieu ne doit pas dépasser :max caractères.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être comprise entre :min et :max.',
            'latitude.required_with' => 'La latitude est obligatoire quand la longitude est renseignée.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être comprise entre :min et :max.',
            'longitude.required_with' => 'La longitude est obligatoire quand la latitude est renseignée.',
            'temperature.numeric' => 'La température doit être un nombre.',
            'temperature.between' => 'La température doit être comprise entre :min et :max °C.',
            'description.max' => 'La description ne doit pas dépasser :max caractères.',
            'date_etape.required' => 'La date de l\'étape est obligatoire.',
            'date_etape.date' => 'La date de l\'étape n\'est pas une date valide.',
            'date_etape.before_or_equal' => 'La date de l\'étape ne peut pas être dans le futur.',
        ];
    }
}
