<?php

namespace App\Http\Requests;

use App\Models\Livraison;

/**
 * Contrôle de saisie de la modification d'une livraison : mêmes règles que la création,
 * le numéro de suivi restant unique en ignorant la livraison en cours.
 */
class UpdateLivraisonRequest extends StoreLivraisonRequest
{
    /**
     * Une livraison en retard doit rester modifiable : la date prévue ne peut pas précéder
     * la création de la livraison, mais elle peut être passée.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creation = ($this->livraisonCourante()?->created_at ?? now())->toDateString();

        return [
            'date_livraison_prevue' => ['required', 'date', 'after_or_equal:'.$creation, 'before_or_equal:'.now()->addMonths(3)->toDateString()],
        ] + parent::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_livraison_prevue.after_or_equal' => 'La date de livraison prévue ne peut pas être antérieure à la création de la livraison.',
        ] + parent::messages();
    }

    /**
     * Livraison en cours d'édition, fournie par la route (paramètre {livraison}).
     */
    protected function livraisonCourante(): ?Livraison
    {
        $livraison = $this->route('livraison');

        return $livraison instanceof Livraison ? $livraison : Livraison::find($livraison);
    }
}
