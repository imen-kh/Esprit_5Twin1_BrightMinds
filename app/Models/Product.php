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
        return self::formatAmount($this->price);
    }

    /**
     * Montant en dinars : espace pour les milliers, virgule pour les millimes,
     * et pas de « ,000 » quand le montant est rond (24 et non 24,000).
     */
    public static function formatAmount(float|int|string|null $amount): string
    {
        $formatted = number_format((float) $amount, 3, ',', ' ');

        return str_ends_with($formatted, ',000') ? substr($formatted, 0, -4) : $formatted;
    }

    /**
     * URL de la photo : URL Cloudinary telle quelle, ou ancien fichier du stockage local.
     */
    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return str_starts_with($this->image, 'http') ? $this->image : asset('storage/'.$this->image);
    }

    // ajouté par le module Traçabilité
    public function lots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lot::class, 'produit_id');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function paniers()
    {
        return $this->belongsToMany(Panier::class, 'panier_produit')->withPivot('quantite')->withTimestamps();
    }

    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_produit')->withPivot('quantite', 'prix_unitaire', 'sous_total')->withTimestamps();
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
