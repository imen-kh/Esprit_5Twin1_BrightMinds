<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Harvest extends Model
{
    public const PLANIFIEE = 'planifiee';

    public const EN_COURS = 'en_cours';

    public const RECOLTEE = 'recoltee';

    protected $fillable = [
        'user_id',
        'product_id',
        'culture_name',
        'planted_at',
        'area_hectares',
        'estimated_kg',
        'harvested_kg',
        'planned_harvest_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'planted_at' => 'date',
            'planned_harvest_at' => 'date',
            'area_hectares' => 'decimal:2',
            'estimated_kg' => 'decimal:1',
            'harvested_kg' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function nom(): string
    {
        if (filled($this->culture_name)) {
            return (string) $this->culture_name;
        }

        return (string) ($this->product?->name ?? '');
    }

    public function libelleStatut(): string
    {
        return match ($this->status) {
            self::EN_COURS => 'En cours',
            self::RECOLTEE => 'Récoltée',
            default => 'Planifiée',
        };
    }

    public function classeStatut(): string
    {
        return match ($this->status) {
            self::EN_COURS => 'bg-success',
            self::RECOLTEE => 'bg-secondary',
            default => 'bg-warning text-dark',
        };
    }

    public function couleurCalendrier(): string
    {
        return match ($this->status) {
            self::EN_COURS => '#81c408',
            self::RECOLTEE => '#6c757d',
            default => '#ffb524',
        };
    }

    public static function formaterKg(float|int|string|null $kg): string
    {
        if ($kg === null || $kg === '') {
            return '—';
        }

        $texte = number_format((float) $kg, 1, ',', ' ');

        return str_ends_with($texte, ',0') ? substr($texte, 0, -2) : $texte;
    }
}
