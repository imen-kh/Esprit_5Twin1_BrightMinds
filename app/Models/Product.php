<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    protected $fillable = [
        'name', 'description', 'price', 'origin', 'ingredients',
        'status', 'category_id', 'user_id',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:3'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bilansEnvironnementaux(): HasMany
    {
        return $this->hasMany(BilanEnvironnemental::class, 'produit_id');
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class, 'produit_id');
    }
}
