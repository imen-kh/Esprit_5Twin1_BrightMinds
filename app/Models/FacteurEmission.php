<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacteurEmission extends Model
{
    use HasFactory;

    protected $table = 'facteurs_emission';

    protected $fillable = [
        'nom',
        'code',
        'categorie',
        'valeur',
        'unite',
        'source',
        'description',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:4',
            'actif' => 'boolean',
        ];
    }

    public function bilans(): HasMany
    {
        return $this->hasMany(BilanEnvironnemental::class, 'facteur_emission_id');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeCategorie(Builder $query, string $cat): Builder
    {
        return $query->where('categorie', $cat);
    }

    public function badgeColor(): string
    {
        return match ($this->categorie) {
            'transport' => 'info',
            'production' => 'success',
            'emballage' => 'warning',
            'energie' => 'danger',
            'eau' => 'primary',
            default => 'secondary',
        };
    }
}
