<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Problème récurrent détecté sur un produit : plusieurs réclamations et avis négatifs rapprochés.
 */
class AlerteQualite extends Model
{
    protected $table = 'alertes_qualite';

    public const GRAVITES = [
        'faible' => ['label' => 'Faible', 'badge' => 'bg-info'],
        'moyenne' => ['label' => 'Moyenne', 'badge' => 'bg-warning text-dark'],
        'elevee' => ['label' => 'Élevée', 'badge' => 'bg-danger'],
    ];

    public const STATUTS = [
        'ouverte' => ['label' => 'À traiter', 'badge' => 'bg-danger'],
        'rappel_declenche' => ['label' => 'Rappel déclenché', 'badge' => 'bg-dark'],
        'classee' => ['label' => 'Classée', 'badge' => 'bg-secondary'],
    ];

    protected $fillable = [
        'produit_id',
        'lot_id',
        'nb_signaux',
        'signature',
        'signaux',
        'titre',
        'resume',
        'gravite',
        'recommandation',
        'rappel_conseille',
        'motif_rappel',
        'analyse_ia',
        'statut',
        'traitee_par',
        'traitee_at',
    ];

    protected function casts(): array
    {
        return [
            'signaux' => 'array',
            'rappel_conseille' => 'boolean',
            'analyse_ia' => 'boolean',
            'traitee_at' => 'datetime',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function traiteePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traitee_par');
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function reclamations(): Collection
    {
        return Reclamation::query()->with('user')->whereIn('id', $this->signaux['reclamations'] ?? [])->latest()->get();
    }

    /**
     * @return Collection<int, Avis>
     */
    public function avisNegatifs(): Collection
    {
        return Avis::query()->with('user')->whereIn('id', $this->signaux['avis'] ?? [])->latest()->get();
    }

    public function graviteLibelle(): string
    {
        return self::GRAVITES[$this->gravite]['label'] ?? 'Moyenne';
    }

    public function graviteBadge(): string
    {
        return self::GRAVITES[$this->gravite]['badge'] ?? 'bg-warning text-dark';
    }

    public function statutLibelle(): string
    {
        return self::STATUTS[$this->statut]['label'] ?? 'À traiter';
    }

    public function statutBadge(): string
    {
        return self::STATUTS[$this->statut]['badge'] ?? 'bg-secondary';
    }
}
