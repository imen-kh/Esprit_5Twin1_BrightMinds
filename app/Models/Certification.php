<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    /** @use HasFactory<\Database\Factories\CertificationFactory> */
    use HasFactory;

    protected $fillable = [
        'label_id',
        'product_id',
        'acteur_id',
        'numero',
        'date_obtention',
        'date_expiration',
        'fichier_pdf',
        'statut',
        'motif_refus',
    ];

    protected function casts(): array
    {
        return [
            'date_obtention' => 'date',
            'date_expiration' => 'date',
        ];
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acteur_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('statut', 'validee');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('statut', 'validee')
            ->where(function (Builder $query): void {
                $query->whereNull('date_expiration')->orWhereDate('date_expiration', '>=', today());
            });
    }

    public function scopeRefused(Builder $query): Builder
    {
        return $query->where('statut', 'refusee');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('date_expiration')->whereDate('date_expiration', '<', today());
    }

    public function scopeForActeur(Builder $query, int|string $userId): Builder
    {
        return $query->where('acteur_id', $userId);
    }

    public function scopeForProducer(Builder $query, int|string $userId): Builder
    {
        return $query->forActeur($userId);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->date_expiration !== null && $this->date_expiration->isBefore(today());
    }
}
