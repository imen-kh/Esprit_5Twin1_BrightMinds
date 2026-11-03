<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const ENTREE = 'entree';

    public const SORTIE = 'sortie';

    public const PERTE = 'perte';

    public const MANUEL = 'manuel';

    public const RECOLTE = 'recolte';

    public const COMMANDE = 'commande';

    protected $fillable = [
        'stock_item_id',
        'type',
        'quantite_kg',
        'source',
        'motif',
        'harvest_id',
        'commande_produit_id',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantite_kg' => 'decimal:1',
            'moved_at' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function libelleType(): string
    {
        return match ($this->type) {
            self::SORTIE => 'Sortie',
            self::PERTE => 'Perte',
            default => 'Entrée',
        };
    }

    public function libelleSource(): string
    {
        return match ($this->source) {
            self::RECOLTE => 'Récolte',
            self::COMMANDE => 'Commande confirmée',
            default => 'Saisie manuelle',
        };
    }
}
