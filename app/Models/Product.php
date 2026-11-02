<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, Searchable;

    public const DRAFT = 'draft';

    public const PENDING = 'pending_review';

    public const CHANGES = 'changes_requested';

    public const REJECTED = 'rejected';

    public const PUBLISHED = 'published';

    protected $fillable = [
        'name', 'description', 'price', 'origin', 'image', 'ingredients',
        'status', 'review_note', 'reviewed_at', 'reviewed_by', 'ai_analysis', 'quality_analysis',
        'type_produit', 'category_id', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'reviewed_at' => 'datetime',
            'ai_analysis' => 'array',
            'quality_analysis' => 'array',
        ];
    }

    public function libelleStatut(): string
    {
        return match ($this->status) {
            self::PUBLISHED => 'Publié',
            self::PENDING => 'En attente de validation',
            self::CHANGES => 'Modification demandée',
            self::REJECTED => 'Refusé',
            default => 'Brouillon',
        };
    }

    public function classeStatut(): string
    {
        return match ($this->status) {
            self::PUBLISHED => 'bg-success',
            self::PENDING => 'bg-warning text-dark',
            self::CHANGES => 'bg-info text-dark',
            self::REJECTED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Champs réellement stockés, envoyés à Meilisearch. Les brouillons ne sont pas indexés.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('category');

        return [
            'name' => (string) $this->name,
            'description' => (string) $this->description,
            'origin' => (string) $this->origin,
            'ingredients' => (string) $this->ingredients,
            'category' => (string) $this->category?->name,
            'price' => (float) $this->price,
            'status' => (string) $this->status,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
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
     * Photos du template utilisées quand le produit n'a pas de photo, choisies par mot-clé du nom.
     * L'ordre compte : « pomme de terre » est testé avant « pomme ».
     */
    private const IMAGES_PAR_DEFAUT = [
        'fraise' => 'fruite-item-2.jpg',
        'framboise' => 'fruite-item-2.jpg',
        'confiture' => 'fruite-item-2.jpg',
        'orange' => 'fruite-item-1.jpg',
        'citron' => 'fruite-item-1.jpg',
        'agrume' => 'fruite-item-1.jpg',
        'banane' => 'fruite-item-3.jpg',
        'abricot' => 'fruite-item-4.jpg',
        'raisin' => 'fruite-item-5.jpg',
        'pomme de terre' => 'vegetable-item-5.jpg',
        'pomme' => 'fruite-item-6.jpg',
        'tomate' => 'vegetable-item-1.jpg',
        'harissa' => 'vegetable-item-4.jpg',
        'piment' => 'vegetable-item-4.jpg',
        'poivron' => 'vegetable-item-4.jpg',
        'persil' => 'vegetable-item-6.jpg',
    ];

    /**
     * URL de la photo, toujours renseignée : URL Cloudinary telle quelle, ancien fichier du
     * stockage local s'il est présent sur cette machine, sinon une photo par défaut.
     */
    public function imageUrl(): string
    {
        if ($this->aUnePhoto()) {
            return str_starts_with($this->image, 'http') ? $this->image : asset('storage/'.$this->image);
        }

        $nom = mb_strtolower((string) $this->name);

        foreach (self::IMAGES_PAR_DEFAUT as $motCle => $fichier) {
            if (str_contains($nom, $motCle)) {
                return asset('front/img/'.$fichier);
            }
        }

        return asset('front/img/banner-fruits.jpg');
    }

    /**
     * Le produit a-t-il une photo réellement affichable ? Une ancienne photo locale ne compte
     * que si son fichier existe ici (le dossier storage/ n'est pas partagé par Git).
     */
    public function aUnePhoto(): bool
    {
        if (! $this->image) {
            return false;
        }

        return str_starts_with($this->image, 'http') || Storage::disk('public')->exists($this->image);
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

    public function bilansEnvironnementaux(): HasMany
    {
        return $this->hasMany(BilanEnvironnemental::class, 'produit_id');
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class, 'produit_id');
    }
}
