<?php

namespace App\Http\Requests;

use App\Models\Lot;
use App\Services\MatieresPremieresService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation de la création d'un lot (module Traçabilité).
 * UpdateLotRequest en hérite : seule la référence au lot en cours d'édition change.
 */
class StoreLotRequest extends FormRequest
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
     * Lot en cours d'édition (aucun à la création).
     */
    protected function lotCourant(): ?Lot
    {
        return null;
    }

    /**
     * Seuls un transformateur et l'admin peuvent rattacher un lot à un lot d'origine.
     * Pour les autres acteurs, le champ n'est pas proposé et toute valeur envoyée est ignorée :
     * un lot de matière première n'a pas d'origine (sinon il ne pourrait plus recevoir d'étape de production).
     */
    protected function prepareForValidation(): void
    {
        if (! Lot::origineAutoriseePour($this->user())) {
            $this->merge(['lot_origine_id' => null]);
        }
    }

    /**
     * Règles de validation d'un lot.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $lot = $this->lotCourant();

        // Un acteur ne peut créer un lot que pour l'un de SES produits ; l'admin pour tous
        $produitExiste = Rule::exists('products', 'id');

        if (! $user->isAdmin()) {
            $produitExiste->where('user_id', $user->id);
        }

        return [
            'code' => ['required', 'regex:/^LOT-\d{4}-\d{4}$/', Rule::unique('lots', 'code')->ignore($lot?->id)],
            'produit_id' => ['required', $produitExiste],
            // Le lot d'origine doit exister et ne peut pas être le lot lui-même
            'lot_origine_id' => ['nullable', 'integer', Rule::exists('lots', 'id'), Rule::notIn(array_filter([$lot?->id]))],
            'date_production' => ['required', 'date', 'before_or_equal:today'],
            'date_peremption' => ['nullable', 'date', 'after:date_production'],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unite' => ['required', Rule::in(Lot::UNITES)],
            'statut' => ['required', Rule::in(array_keys(Lot::STATUTS))],
            'notes' => ['nullable', 'max:1000'],
        ];
    }

    /**
     * Règles métier qui dépendent de la base : cohérence avec le lot d'origine.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->verifierLotExistant($validator));

        $validator->after(function (Validator $validator) {
            // Inutile d'aller plus loin si les champs concernés sont déjà invalides
            if ($validator->errors()->hasAny(['lot_origine_id', 'date_production']) || ! $this->filled('lot_origine_id')) {
                return;
            }

            $origine = Lot::find($this->input('lot_origine_id'));

            if (! $origine) {
                return;
            }

            // Un transformateur ne transforme que les matières premières qu'il a commandées.
            // En modification, l'origine déjà enregistrée reste acceptée.
            $dejaEnregistree = $this->lotCourant()?->lot_origine_id === $origine->id;

            if (! $dejaEnregistree && ! app(MatieresPremieresService::class)->lotAccessible($this->user(), $origine)) {
                $validator->errors()->add('lot_origine_id', 'Ce lot d\'origine ne fait pas partie des matières premières que vous avez commandées.');

                return;
            }

            // Un lot transformé ne peut pas être produit avant sa matière première
            if ($origine->date_production->gt($this->date('date_production'))) {
                $validator->errors()->add(
                    'date_production',
                    'La date de production ne peut pas être antérieure à celle du lot d\'origine ('
                    .$origine->date_production->format('d/m/Y').').'
                );
            }

            // En édition : le lot d'origine ne doit pas être lui-même issu du lot courant (boucle)
            $courant = $this->lotCourant();
            $dejaVus = [];

            for ($ancetre = $origine; $courant && $ancetre && ! in_array($ancetre->id, $dejaVus, true); $ancetre = $ancetre->lotOrigine) {
                $dejaVus[] = $ancetre->id;

                if ($ancetre->id === $courant->id) {
                    $validator->errors()->add('lot_origine_id', 'Ce lot d\'origine est lui-même issu du lot en cours : la chaîne formerait une boucle.');
                    break;
                }
            }
        });
    }

    /**
     * En modification : le lot doit rester cohérent avec ce qui a déjà été enregistré dessus
     * (ses étapes et les lots transformés qui en sont issus).
     */
    protected function verifierLotExistant(Validator $validator): void
    {
        $lot = $this->lotCourant();

        if (! $lot || $validator->errors()->has('date_production')) {
            return;
        }

        $production = $this->date('date_production');
        $premiereEtape = $lot->etapes()->first();

        // La production ne peut pas être datée après une étape déjà déclarée
        if ($premiereEtape && $production->gt($premiereEtape->date_etape->copy()->startOfDay())) {
            $validator->errors()->add(
                'date_production',
                'La date de production ne peut pas être postérieure à la première étape du lot ('
                .$premiereEtape->date_etape->format('d/m/Y').').'
            );
        }

        // Ni après la production d'un lot transformé issu de celui-ci
        $premierDerive = $lot->lotsDerives()->orderBy('date_production')->first();

        if ($premierDerive && $production->gt($premierDerive->date_production)) {
            $validator->errors()->add(
                'date_production',
                'La date de production ne peut pas être postérieure à celle du lot '.$premierDerive->code
                .', fabriqué à partir de ce lot ('.$premierDerive->date_production->format('d/m/Y').').'
            );
        }

        // Un lot qui a déjà son étape de production est une matière première : il n'a pas de lot d'origine
        if ($this->filled('lot_origine_id') && ! $validator->errors()->has('lot_origine_id')
            && $lot->etapes()->where('type_etape', 'production')->exists()) {
            $validator->errors()->add('lot_origine_id', 'Ce lot a déjà une étape de production : il ne peut pas être rattaché à un lot d\'origine.');
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
            'code.required' => 'Le code du lot est obligatoire.',
            'code.regex' => 'Le code doit respecter le format LOT-AAAA-NNNN (ex. LOT-2026-0001).',
            'code.unique' => 'Ce code de lot est déjà utilisé.',
            'produit_id.required' => 'Le produit est obligatoire.',
            'produit_id.exists' => 'Ce produit n\'existe pas ou ne vous appartient pas.',
            'lot_origine_id.integer' => 'Le lot d\'origine choisi n\'est pas valide.',
            'lot_origine_id.exists' => 'Le lot d\'origine choisi n\'existe pas.',
            'lot_origine_id.not_in' => 'Un lot ne peut pas être son propre lot d\'origine.',
            'date_production.required' => 'La date de production est obligatoire.',
            'date_production.date' => 'La date de production n\'est pas une date valide.',
            'date_production.before_or_equal' => 'La date de production ne peut pas être dans le futur.',
            'date_peremption.date' => 'La date de péremption n\'est pas une date valide.',
            'date_peremption.after' => 'La date de péremption doit être postérieure à la date de production.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité doit être d\'au moins :min.',
            'quantite.max' => 'La quantité ne peut pas dépasser :max.',
            'unite.required' => 'L\'unité est obligatoire.',
            'unite.in' => 'L\'unité choisie n\'est pas valide.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut choisi n\'est pas valide.',
            'notes.max' => 'Les notes ne doivent pas dépasser :max caractères.',
        ];
    }
}
