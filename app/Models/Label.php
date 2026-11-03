<?php

namespace App\Models;

use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
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

    /**
     * URL du logo, toujours renseignée : le fichier envoyé s'il existe sur cette machine
     * (le dossier storage/ n'est pas partagé par Git), sinon le logo par défaut du label,
     * sinon un logo générique.
     */
    public function getLogoUrlAttribute(): string
    {
        $path = ltrim((string) $this->logo, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return '/storage/'.$path;
        }

        $fichier = 'front/img/labels/'.Str::slug((string) $this->nom).'.svg';

        return asset(is_file(public_path($fichier)) ? $fichier : 'front/img/labels/label.svg');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
