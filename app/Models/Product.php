<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'origin', 'image', 'ingredients',
        'status', 'type_produit', 'category_id', 'user_id',
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

    public function formattedPrice(): string
    {
        $formatted = number_format((float) $this->price, 3, ',', ' ');

        return str_ends_with($formatted, ',000') ? substr($formatted, 0, -4) : $formatted;
    }

    // ajouté par le module Traçabilité
    public function lots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lot::class, 'produit_id');
    }

    public function paniers()
    {
        return $this->belongsToMany(Panier::class, 'panier_produit')->withPivot('quantite')->withTimestamps();
    }

    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_produit')->withPivot('quantite', 'prix_unitaire', 'sous_total')->withTimestamps();
    }
}
