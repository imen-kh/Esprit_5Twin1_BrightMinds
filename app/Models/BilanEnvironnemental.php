<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilanEnvironnemental extends Model
{
    use HasFactory;

    protected $table = 'bilans_environnementaux';

    protected $fillable = [
        'produit_id',
        'nom_produit',
        'facteur_emission_id',
        'facteur_production_id',
        'facteur_transport_id',
        'facteur_emballage_id',
        'quantite_produit',
        'unite_mesure',
        'origine_geographique',
        'distance_km',
        'mode_transport',
        'type_emballage',
        'poids_emballage_g',
        'mode_culture',
        'consommation_eau_l',
        'co2_production',
        'co2_transport',
        'co2_emballage',
        'co2_total',
        'score_numerique',
        'eco_score',
        'alternative_locale',
        'recommandation_ia',
        'reduction_co2_estimee_pourcent',
        'statut',
        'commentaires',
    ];

    protected function casts(): array
    {
        return [
            'quantite_produit' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'poids_emballage_g' => 'decimal:2',
            'consommation_eau_l' => 'decimal:2',
            'co2_production' => 'decimal:3',
            'co2_transport' => 'decimal:3',
            'co2_emballage' => 'decimal:3',
            'co2_total' => 'decimal:3',
            'reduction_co2_estimee_pourcent' => 'decimal:2',
            'score_numerique' => 'integer',
        ];
    }

    public function facteurPrincipal(): BelongsTo
    {
        return $this->belongsTo(FacteurEmission::class, 'facteur_emission_id');
    }

    public function facteurProduction(): BelongsTo
    {
        return $this->belongsTo(FacteurEmission::class, 'facteur_production_id');
    }

    public function facteurTransport(): BelongsTo
    {
        return $this->belongsTo(FacteurEmission::class, 'facteur_transport_id');
    }

    public function facteurEmballage(): BelongsTo
    {
        return $this->belongsTo(FacteurEmission::class, 'facteur_emballage_id');
    }

    public function scopeValide(Builder $query): Builder
    {
        return $query->where('statut', 'valide');
    }

    public function ecoScoreColor(): string
    {
        return match ($this->eco_score) {
            'A' => '#1e7e34', // Vert foncé
            'B' => '#28a745', // Vert
            'C' => '#ffc107', // Jaune / Orange
            'D' => '#fd7e14', // Orange vif
            'E' => '#dc3545', // Rouge
            default => '#6c757d',
        };
    }

    public function ecoScoreBadgeClass(): string
    {
        return match ($this->eco_score) {
            'A' => 'bg-success',
            'B' => 'bg-info',
            'C' => 'bg-warning text-dark',
            'D' => 'bg-danger',
            'E' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function ecoScoreAppreciation(): string
    {
        return match ($this->eco_score) {
            'A' => 'Excellent — Très faible impact environnemental',
            'B' => 'Bon — Faible impact environnemental',
            'C' => 'Moyen — Impact environnemental modéré',
            'D' => 'Élevé — Empreinte écologique importante',
            'E' => 'Critique — Empreinte écologique très élevée',
            default => 'Non évalué',
        };
    }
}
