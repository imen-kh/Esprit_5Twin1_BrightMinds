<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'culture_name',
        'seuil_kg',
        'kg_par_unite',
    ];

    protected function casts(): array
    {
        return [
            'seuil_kg' => 'decimal:1',
            'kg_par_unite' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function disponible(): float
    {
        $entrees = (float) $this->movements()->where('type', StockMovement::ENTREE)->sum('quantite_kg');
        $sorties = (float) $this->movements()->whereIn('type', [StockMovement::SORTIE, StockMovement::PERTE])->sum('quantite_kg');

        return round($entrees - $sorties, 1);
    }

    public function niveau(): string
    {
        $disponible = $this->disponible();

        if ($disponible <= 0) {
            return 'rupture';
        }

        if ($disponible <= (float) $this->seuil_kg) {
            return 'faible';
        }

        return 'ok';
    }
}
