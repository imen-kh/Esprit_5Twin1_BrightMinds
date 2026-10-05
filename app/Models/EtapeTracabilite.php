<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Étape de traçabilité d'un lot (module Traçabilité), réalisée par un acteur de la chaîne.
 */
class EtapeTracabilite extends Model
{
    /** @use HasFactory<\Database\Factories\EtapeTracabiliteFactory> */
    use HasFactory;

    protected $table = 'etape_tracabilites';

    /**
     * Types d'étape : libellé français, icône Boxicons et couleur Bootstrap.
     */
    public const TYPES = [
        'production' => ['label' => 'Production', 'icone' => 'bx-leaf', 'couleur' => 'success'],
        'transformation' => ['label' => 'Transformation', 'icone' => 'bx-cog', 'couleur' => 'warning'],
        'transport' => ['label' => 'Transport', 'icone' => 'bx-car', 'couleur' => 'info'],
        'stockage' => ['label' => 'Stockage', 'icone' => 'bx-package', 'couleur' => 'secondary'],
        'distribution' => ['label' => 'Distribution', 'icone' => 'bx-store', 'couleur' => 'primary'],
    ];

    /**
     * Icônes Font Awesome des types d'étape, pour le front office (Boxicons n'y est pas chargé).
     */
    public const ICONES_FRONT = [
        'production' => 'fa-seedling',
        'transformation' => 'fa-industry',
        'transport' => 'fa-truck',
        'stockage' => 'fa-warehouse',
        'distribution' => 'fa-store',
    ];

    /**
     * Types d'étape qu'un acteur a le droit de déclarer, selon son type.
     */
    public const TYPES_PAR_ACTEUR = [
        'producteur' => ['production'],
        'transformateur' => ['production', 'transformation'],
        'distributeur' => ['transport', 'stockage', 'distribution'],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lot_id',
        'user_id',
        'type_etape',
        'titre',
        'lieu',
        'latitude',
        'longitude',
        'date_etape',
        'temperature',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_etape' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'temperature' => 'decimal:2',
        ];
    }

    /**
     * Lot concerné par l'étape.
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * Acteur qui a réalisé l'étape.
     */
    public function acteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Types d'étape qu'un utilisateur peut déclarer : tous pour l'admin, ceux de son métier pour un acteur.
     *
     * @return array<string, array{label: string, icone: string, couleur: string}>
     */
    public static function typesPour(User $user): array
    {
        if ($user->isAdmin()) {
            return self::TYPES;
        }

        return array_intersect_key(self::TYPES, array_flip(self::TYPES_PAR_ACTEUR[$user->type_acteur] ?? []));
    }

    /**
     * Libellé, icône et couleur du type de cette étape (accessor « type »).
     *
     * @return array{label: string, icone: string, couleur: string}
     */
    public function getTypeAttribute(): array
    {
        return self::TYPES[$this->type_etape] ?? ['label' => ucfirst((string) $this->type_etape), 'icone' => 'bx-map-pin', 'couleur' => 'secondary'];
    }

    /**
     * Température prête à afficher, ex. « 4,5 °C » (accessor « temperature_formatee »), ou null.
     */
    public function getTemperatureFormateeAttribute(): ?string
    {
        if ($this->temperature === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->temperature, 2, ',', ' '), '0'), ',').' °C';
    }

    /**
     * L'utilisateur peut-il consulter cette étape ? Son auteur, l'admin, ou toute personne qui voit le lot.
     */
    public function estVisiblePar(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id || (bool) $this->lot?->estVisiblePar($user);
    }

    /**
     * Modifier ou supprimer une étape est réservé à son auteur et à l'admin.
     */
    public function estModifiablePar(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }
}
