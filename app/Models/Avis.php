<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Avis d'un client sur un produit : note de 1 à 5, commentaire, analyse IA par aspect.
 */
class Avis extends Model
{
    /** @use HasFactory<\Database\Factories\AvisFactory> */
    use HasFactory;

    // « avis » est invariable : sans cette ligne, Laravel chercherait la table « avis » au pluriel anglais
    protected $table = 'avis';

    public const STATUTS = [
        'publie' => ['label' => 'Publié', 'badge' => 'bg-success'],
        'en_moderation' => ['label' => 'En modération', 'badge' => 'bg-warning text-dark'],
        'masque' => ['label' => 'Masqué', 'badge' => 'bg-secondary'],
    ];

    /**
     * Aspects notés par l'IA à partir du commentaire.
     */
    public const ASPECTS = [
        'gout' => ['label' => 'Goût', 'icone' => 'fa-utensils'],
        'fraicheur' => ['label' => 'Fraîcheur', 'icone' => 'fa-leaf'],
        'emballage' => ['label' => 'Emballage', 'icone' => 'fa-box-open'],
        'prix' => ['label' => 'Rapport qualité/prix', 'icone' => 'fa-tag'],
        'livraison' => ['label' => 'Livraison', 'icone' => 'fa-truck'],
    ];

    protected $fillable = [
        'user_id',
        'produit_id',
        'note',
        'titre',
        'commentaire',
        'statut',
        'achat_verifie',
        'sentiment',
        'aspects',
        'moderation_raison',
        'probleme_detecte',
        'reponse_producteur',
        'reponse_producteur_at',
        'utile_count',
        'signalements_count',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
            'achat_verifie' => 'boolean',
            'probleme_detecte' => 'boolean',
            'aspects' => 'array',
            'reponse_producteur_at' => 'datetime',
            'utile_count' => 'integer',
            'signalements_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(AvisReaction::class);
    }

    /**
     * Réclamation ouverte à partir de cet avis (avis négatif converti).
     */
    public function reclamation(): HasOne
    {
        return $this->hasOne(Reclamation::class);
    }

    public function scopePublies(Builder $query): Builder
    {
        return $query->where('statut', 'publie');
    }

    /**
     * Le client a-t-il reçu ce produit dans une commande livrée ?
     */
    public static function estAchatVerifie(User $user, Product $produit): bool
    {
        return Commande::query()
            ->where('user_id', $user->id)
            ->where('statut', 'livree')
            ->whereHas('products', fn (Builder $q) => $q->whereKey($produit->id))
            ->exists();
    }

    public function statutLibelle(): string
    {
        return self::STATUTS[$this->statut]['label'] ?? 'Publié';
    }

    public function statutBadge(): string
    {
        return self::STATUTS[$this->statut]['badge'] ?? 'bg-secondary';
    }

    /**
     * Avis négatif décrivant un vrai problème : on propose au client d'ouvrir une réclamation.
     */
    public function meriteReclamation(): bool
    {
        return $this->note <= 2 && $this->probleme_detecte && ! $this->reclamation()->exists();
    }

    public function initiales(): string
    {
        $nom = trim((string) $this->user?->name);

        return $nom === '' ? '?' : mb_strtoupper(mb_substr($nom, 0, 1));
    }
}
