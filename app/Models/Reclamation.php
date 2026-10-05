<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reclamation extends Model
{
    /** @use HasFactory<\Database\Factories\ReclamationFactory> */
    use HasFactory;

    public const STATUTS = ['en_attente', 'en_cours', 'resolue', 'rejetee'];

    public const PRIORITES = ['faible', 'moyenne', 'haute', 'urgente'];

    protected function casts(): array
    {
        return [
            'commande_id' => 'integer',
        ];
    }

    protected $fillable = [
        'user_id',
        'produit_id',
        'commande_id',
        'sujet',
        'description',
        'statut',
        'priorite',
        'sentiment',
        'priorite_suggeree',
        'suggestion_reponse',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseReclamation::class);
    }

    public function statutLibelle(): string
    {
        return match ($this->statut) {
            'en_cours' => 'En cours',
            'resolue' => 'Résolue',
            'rejetee' => 'Rejetée',
            default => 'En attente',
        };
    }

    public function statutBadge(): string
    {
        return match ($this->statut) {
            'en_cours' => 'bg-info',
            'resolue' => 'bg-success',
            'rejetee' => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    }

    public function prioriteLibelle(): string
    {
        return match ($this->priorite) {
            'faible' => 'Faible',
            'haute' => 'Haute',
            'urgente' => 'Urgente',
            default => 'Moyenne',
        };
    }

    public function prioriteBadge(): string
    {
        return match ($this->priorite) {
            'faible' => 'bg-success',
            'haute' => 'bg-warning text-dark',
            'urgente' => 'bg-danger',
            default => 'bg-info',
        };
    }
}
