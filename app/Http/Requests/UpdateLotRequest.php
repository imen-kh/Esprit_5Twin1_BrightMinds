<?php

namespace App\Http\Requests;

use App\Models\Lot;

/**
 * Validation de la modification d'un lot : mêmes règles que la création, mais le code
 * reste unique en ignorant le lot en cours et celui-ci ne peut pas être sa propre origine.
 */
class UpdateLotRequest extends StoreLotRequest
{
    /**
     * Modifier un lot est réservé au propriétaire du produit et à l'admin.
     */
    public function authorize(): bool
    {
        return parent::authorize() && (bool) $this->lotCourant()?->estModifiablePar($this->user());
    }

    /**
     * Lot en cours d'édition, fourni par la route resource (paramètre {lot}).
     */
    protected function lotCourant(): ?Lot
    {
        $lot = $this->route('lot');

        return $lot instanceof Lot ? $lot : Lot::find($lot);
    }
}
