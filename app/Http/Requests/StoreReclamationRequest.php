<?php

namespace App\Http\Requests;

use App\Models\Lot;
use App\Models\Reclamation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code_lot')) {
            $this->merge(['code_lot' => strtoupper(trim((string) $this->input('code_lot')))]);
        }
    }

    public function rules(): array
    {
        $commande = Rule::exists('commandes', 'id');
        // Un client ne rattache que ses propres commandes ; l'administrateur saisit pour le compte d'un client
        if (! $this->user()?->isAdmin()) {
            $commande->where('user_id', $this->user()?->id);
        }

        return [
            'produit_id' => ['required', 'integer', 'exists:products,id'],
            'commande_id' => ['nullable', 'integer', $commande],
            'code_lot' => ['nullable', 'string', 'max:40', function (string $attribut, mixed $valeur, Closure $echec) {
                $existe = Lot::query()->where('code', $valeur)->where('produit_id', $this->input('produit_id'))->exists();
                if (! $existe) {
                    $echec('Ce code de lot ne correspond pas au produit choisi.');
                }
            }],
            'sujet' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priorite' => ['required', 'string', Rule::in(Reclamation::PRIORITES)],
        ];
    }

    /**
     * Données prêtes pour GestionReclamationService : le code du lot devient lot_id.
     *
     * @return array<string, mixed>
     */
    public function donnees(): array
    {
        $donnees = collect($this->validated())->except('code_lot')->all();
        $donnees['lot_id'] = $this->filled('code_lot')
            ? Lot::query()->where('code', $this->input('code_lot'))->value('id')
            : null;

        return $donnees;
    }

    public function messages(): array
    {
        return [
            'produit_id.required' => 'Le produit est obligatoire.',
            'produit_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'commande_id.exists' => 'Cette commande ne fait pas partie de vos commandes.',
            'sujet.required' => 'Le sujet est obligatoire.',
            'sujet.min' => 'Le sujet doit contenir au moins :min caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'Décrivez le problème en au moins :min caractères.',
            'priorite.required' => 'La priorité est obligatoire.',
        ];
    }
}
