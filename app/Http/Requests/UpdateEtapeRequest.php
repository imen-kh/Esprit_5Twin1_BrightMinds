<?php

namespace App\Http\Requests;

use App\Models\EtapeTracabilite;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Validation de la modification d'une étape : mêmes règles que la création, en excluant
 * l'étape en cours d'édition des contrôles de chronologie.
 */
class UpdateEtapeRequest extends StoreEtapeRequest
{
    /**
     * Modifier une étape est réservé à son auteur et à l'admin.
     */
    public function authorize(): bool
    {
        return parent::authorize() && (bool) $this->etapeCourante()?->estModifiablePar($this->user());
    }

    /**
     * Étape en cours d'édition, fournie par la route resource (paramètre {etape}).
     */
    protected function etapeCourante(): ?EtapeTracabilite
    {
        $etape = $this->route('etape');

        return $etape instanceof EtapeTracabilite ? $etape : EtapeTracabilite::find($etape);
    }

    /**
     * En édition, l'étape garde sa place dans la chaîne : sa date doit rester comprise entre
     * l'étape qui la précède et celle qui la suit. Si elle change de lot, elle s'ajoute à la
     * fin du nouveau lot, comme à la création.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>  $autres  étapes du lot, triées par date
     */
    protected function verifierChronologie(Validator $validator, $autres, Carbon $date, ?EtapeTracabilite $courante): void
    {
        if (! $courante || (int) $this->input('lot_id') !== $courante->lot_id) {
            parent::verifierChronologie($validator, $autres, $date, $courante);

            return;
        }

        $precedente = $autres->last(fn (EtapeTracabilite $etape) => $etape->date_etape->lte($courante->date_etape));
        $suivante = $autres->first(fn (EtapeTracabilite $etape) => $etape->date_etape->gt($courante->date_etape));

        if ($precedente && $date->lt($precedente->date_etape)) {
            $validator->errors()->add(
                'date_etape',
                'La date de l\'étape ne peut pas être antérieure à l\'étape précédente ('
                .$precedente->date_etape->format('d/m/Y H:i').').'
            );
        }

        if ($suivante && $date->gt($suivante->date_etape)) {
            $validator->errors()->add(
                'date_etape',
                'La date de l\'étape ne peut pas être postérieure à l\'étape suivante ('
                .$suivante->date_etape->format('d/m/Y H:i').').'
            );
        }
    }
}
