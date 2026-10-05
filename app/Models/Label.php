<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    /** @use HasFactory<\Database\Factories\LabelFactory> */
    use HasFactory;

    protected $fillable = [
        'nom',
        'organisme',
        'description',
        'logo',
        'duree_validite_mois',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'duree_validite_mois' => 'integer',
        ];
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
