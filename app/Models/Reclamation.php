<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reclamation extends Model
{
    /** @use HasFactory<\Database\Factories\ReclamationFactory> */
    use HasFactory;

    public const STATUTS = ['en_attente', 'en_cours', 'resolue', 'rejetee'];

    public const PRIORITES = ['faible', 'moyenne', 'haute', 'urgente'];

    /** Statuts qui clôturent le dossier : plus de réponse possible, le client peut évaluer le traitement. */
    public const STATUTS_CLOTURES = ['resolue', 'rejetee'];

    /**
     * Catégories proposées par l'analyse IA : libellé et icônes Boxicons (back) / Font Awesome (front).
     */
    public const CATEGORIES = [
        'qualite' => ['label' => 'Qualité du produit', 'icone' => 'bx-error-alt', 'fa' => 'fa-apple-alt'],
        'securite_alimentaire' => ['label' => 'Sécurité alimentaire', 'icone' => 'bx-shield-x', 'fa' => 'fa-biohazard'],
        'livraison' => ['label' => 'Livraison', 'icone' => 'bx-package', 'fa' => 'fa-truck'],
        'emballage' => ['label' => 'Emballage', 'icone' => 'bx-box', 'fa' => 'fa-box-open'],
        'quantite' => ['label' => 'Quantité / poids', 'icone' => 'bx-calculator', 'fa' => 'fa-balance-scale'],
        'information' => ['label' => 'Information produit', 'icone' => 'bx-info-circle', 'fa' => 'fa-info-circle'],
        'autre' => ['label' => 'Autre', 'icone' => 'bx-dots-horizontal-rounded', 'fa' => 'fa-ellipsis-h'],
    ];

    /**
     * Maillon de la chaîne probablement à l'origine du problème (analyse « Qui est responsable ? »).
     */
    public const RESPONSABLES = [
        'producteur' => 'Producteur',
        'transformateur' => 'Transformateur',
        'distributeur' => 'Distributeur / livraison',
        'plateforme' => 'Plateforme NutriTrace',
    ];

    public const SOURCES = [
        'formulaire' => 'Formulaire',
        'assistant' => 'Assistant Sahbi',
        'avis' => 'Avis converti',
    ];

    protected $fillable = [
        'user_id',
        'produit_id',
        'commande_id',
        'lot_id',
        'avis_id',
        'source',
        'sujet',
        'description',
        'statut',
        'priorite',
        'categorie',
        'langue',
        'sentiment',
        'priorite_suggeree',
        'suggestion_reponse',
        'resume_ia',
        'responsable_suggere',
        'responsable_explication',
        'echeance_at',
        'resolue_at',
        'satisfaction_note',
        'satisfaction_commentaire',
    ];

    protected function casts(): array
    {
        return [
            'commande_id' => 'integer',
            'lot_id' => 'integer',
            'satisfaction_note' => 'integer',
            'echeance_at' => 'datetime',
            'resolue_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reclamation $reclamation) {
            $reclamation->statut ??= 'en_attente';
            $reclamation->priorite ??= 'moyenne';
            $reclamation->source ??= 'formulaire';
            $reclamation->echeance_at ??= self::echeancePour($reclamation->priorite, now());
        });

        // La référence reprend l'identifiant : elle est posée juste après l'insertion
        static::created(function (Reclamation $reclamation) {
            if (! $reclamation->reference) {
                $reclamation->forceFill([
                    'reference' => sprintf('REC-%s-%04d', ($reclamation->created_at ?? now())->format('Y'), $reclamation->id),
                ])->saveQuietly();
            }
        });

        static::updating(function (Reclamation $reclamation) {
            if ($reclamation->isDirty('priorite') && ! $reclamation->estCloturee()) {
                $reclamation->echeance_at = self::echeancePour($reclamation->priorite, $reclamation->created_at ?? now());
            }

            if ($reclamation->isDirty('statut')) {
                $reclamation->resolue_at = $reclamation->estCloturee() ? now() : null;
            }
        });
    }

    public static function echeancePour(?string $priorite, Carbon $depart): Carbon
    {
        $heures = config('reclamations.sla_heures.'.($priorite ?: 'moyenne'), 72);

        return $depart->copy()->addHours((int) $heures);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function avis(): BelongsTo
    {
        return $this->belongsTo(Avis::class);
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseReclamation::class);
    }

    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueReclamation::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Ajoute un événement à la chronologie du dossier.
     */
    public function journaliser(string $type, string $libelle, ?User $auteur = null): HistoriqueReclamation
    {
        return $this->historique()->create([
            'type' => $type,
            'libelle' => mb_substr($libelle, 0, 255),
            'user_id' => $auteur?->id,
        ]);
    }

    /**
     * Réclamations qu'un utilisateur peut consulter : les siennes, toutes pour l'admin,
     * celles qui portent sur ses produits pour un acteur de la chaîne.
     */
    public function scopeVisiblesPar(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);

            if ($user->isActeur()) {
                $q->orWhereHas('produit', fn (Builder $produit) => $produit->where('user_id', $user->id));
            }
        });
    }

    public function scopeOuvertes(Builder $query): Builder
    {
        return $query->whereNotIn('statut', self::STATUTS_CLOTURES);
    }

    public function scopeEnRetard(Builder $query): Builder
    {
        return $query->ouvertes()->whereNotNull('echeance_at')->where('echeance_at', '<', now());
    }

    public function estCloturee(): bool
    {
        return in_array($this->statut, self::STATUTS_CLOTURES, true);
    }

    public function estEnRetard(): bool
    {
        return ! $this->estCloturee() && $this->echeance_at !== null && $this->echeance_at->isPast();
    }

    /**
     * Délai de traitement lisible : « Reste 5 h », « En retard de 2 j », « Traitée en 20 h ».
     */
    public function slaLibelle(): string
    {
        if ($this->estCloturee()) {
            return 'Traitée en '.self::duree($this->created_at, $this->resolue_at ?? $this->updated_at);
        }

        if ($this->echeance_at === null) {
            return '—';
        }

        return $this->echeance_at->isPast()
            ? 'En retard de '.self::duree($this->echeance_at, now())
            : 'Reste '.self::duree(now(), $this->echeance_at);
    }

    public function slaBadge(): string
    {
        if ($this->estCloturee()) {
            return $this->slaRespecte() ? 'bg-success' : 'bg-secondary';
        }

        if ($this->echeance_at === null) {
            return 'bg-secondary';
        }

        if ($this->estEnRetard()) {
            return 'bg-danger';
        }

        // Moins d'un quart du délai restant : orange
        $total = max(1, (int) abs(($this->created_at ?? now())->diffInMinutes($this->echeance_at)));
        $restant = (int) abs(now()->diffInMinutes($this->echeance_at));

        return $restant < $total / 4 ? 'bg-warning text-dark' : 'bg-info';
    }

    /**
     * Le traitement a-t-il respecté le délai ? null tant que le dossier est ouvert.
     */
    public function slaRespecte(): ?bool
    {
        if (! $this->estCloturee() || ! $this->echeance_at) {
            return null;
        }

        return ($this->resolue_at ?? $this->updated_at)->lte($this->echeance_at);
    }

    public function peutEtreEvaluee(): bool
    {
        return $this->estCloturee() && $this->satisfaction_note === null;
    }

    public function categorieLibelle(): string
    {
        return self::CATEGORIES[$this->categorie]['label'] ?? 'Non classée';
    }

    public function categorieIcone(): string
    {
        return self::CATEGORIES[$this->categorie]['icone'] ?? 'bx-help-circle';
    }

    public function categorieIconeFront(): string
    {
        return self::CATEGORIES[$this->categorie]['fa'] ?? 'fa-question-circle';
    }

    public function responsableLibelle(): ?string
    {
        return self::RESPONSABLES[$this->responsable_suggere] ?? null;
    }

    public function sourceLibelle(): string
    {
        return self::SOURCES[$this->source] ?? 'Formulaire';
    }

    public function statutLibelle(): string
    {
        return self::statutsLibelles()[$this->statut] ?? 'En attente';
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
        return self::prioritesLibelles()[$this->priorite] ?? 'Moyenne';
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

    /**
     * @return array<string, string>
     */
    public static function statutsLibelles(): array
    {
        return ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'resolue' => 'Résolue', 'rejetee' => 'Rejetée'];
    }

    /**
     * @return array<string, string>
     */
    public static function prioritesLibelles(): array
    {
        return ['faible' => 'Faible', 'moyenne' => 'Moyenne', 'haute' => 'Haute', 'urgente' => 'Urgente'];
    }

    private static function duree(?Carbon $debut, ?Carbon $fin): string
    {
        if (! $debut || ! $fin) {
            return '—';
        }

        $minutes = (int) abs($debut->diffInMinutes($fin));

        return match (true) {
            $minutes < 60 => max(1, $minutes).' min',
            $minutes < 48 * 60 => intdiv($minutes, 60).' h',
            default => intdiv($minutes, 1440).' j',
        };
    }
}
