<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Lot de production d'un produit (module Traçabilité).
 * Un lot possède plusieurs étapes de traçabilité (relation 1-N).
 */
class Lot extends Model
{
    /** @use HasFactory<\Database\Factories\LotFactory> */
    use HasFactory;

    /**
     * Statuts possibles : libellé affiché et couleur du badge Bootstrap.
     */
    public const STATUTS = [
        'en_production' => ['label' => 'En production', 'badge' => 'warning'],
        'en_transit' => ['label' => 'En transit', 'badge' => 'info'],
        'disponible' => ['label' => 'Disponible', 'badge' => 'success'],
        'rappele' => ['label' => 'Rappelé', 'badge' => 'danger'],
    ];

    /**
     * Unités de quantité autorisées.
     */
    public const UNITES = ['kg', 'L', 'unités', 'caisses'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'produit_id',
        'lot_origine_id',
        'date_production',
        'date_peremption',
        'quantite',
        'unite',
        'statut',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_production' => 'date',
            'date_peremption' => 'date',
            'quantite' => 'integer',
        ];
    }

    /**
     * Produit du Catalogue auquel appartient le lot.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    /**
     * Étapes de traçabilité du lot, dans l'ordre chronologique.
     */
    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeTracabilite::class)->orderBy('date_etape');
    }

    /**
     * Lot de matière première dont ce lot est issu (ex. fraises pour une confiture).
     */
    public function lotOrigine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'lot_origine_id');
    }

    /**
     * Lots transformés fabriqués à partir de ce lot.
     */
    public function lotsDerives(): HasMany
    {
        return $this->hasMany(self::class, 'lot_origine_id');
    }

    /**
     * Chaîne complète « de la ferme à l'assiette » : étapes des lots d'origine (récursif)
     * puis du lot courant, triées par date.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>
     */
    public function chaineComplete(): Collection
    {
        $etapes = collect();
        $dejaVus = [];
        $lot = $this;

        // On remonte la chaîne des lots d'origine, en se protégeant d'une éventuelle boucle
        while ($lot && ! in_array($lot->id, $dejaVus, true)) {
            $dejaVus[] = $lot->id;
            $etapes = $etapes->concat($lot->etapes()->with(['acteur', 'lot.produit'])->get());
            $lot = $lot->lotOrigine;
        }

        return $etapes->sortBy('date_etape')->values();
    }

    /**
     * Lignée du lot, du plus ancien lot d'origine jusqu'au lot courant (ex. fraises puis confiture).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Lot>
     */
    public function lignee(): Collection
    {
        $lots = collect();
        $dejaVus = [];
        $lot = $this;

        while ($lot && ! in_array($lot->id, $dejaVus, true)) {
            $dejaVus[] = $lot->id;
            $lots->prepend($lot);
            $lot = $lot->lotOrigine;
        }

        return $lots;
    }

    /**
     * Distance à vol d'oiseau entre deux points GPS, en kilomètres (formule de Haversine).
     */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rayonTerre = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $rayonTerre * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Chiffres clés de la chaîne complète : nombre d'étapes, durée de la première à la dernière
     * étape (en jours) et distance totale approximative parcourue entre étapes successives.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>|null  $etapes  chaîne déjà chargée, sinon chaineComplete()
     * @return array{nombre_etapes: int, duree_jours: int, distance_km: int}
     */
    public function statistiquesChaine(?Collection $etapes = null): array
    {
        $etapes ??= $this->chaineComplete();

        $duree = $etapes->count() > 1
            ? (int) round($etapes->first()->date_etape->diffInHours($etapes->last()->date_etape) / 24)
            : 0;

        // Seules les étapes géolocalisées comptent dans la distance
        $points = $etapes->filter(fn (EtapeTracabilite $e) => $e->latitude !== null && $e->longitude !== null)->values();
        $distance = 0.0;

        for ($i = 1; $i < $points->count(); $i++) {
            $distance += self::distanceKm(
                (float) $points[$i - 1]->latitude,
                (float) $points[$i - 1]->longitude,
                (float) $points[$i]->latitude,
                (float) $points[$i]->longitude,
            );
        }

        return [
            'nombre_etapes' => $etapes->count(),
            'duree_jours' => $duree,
            'distance_km' => (int) round($distance),
        ];
    }

    /**
     * Statut correspondant à un type d'étape : en production tant que le lot est fabriqué,
     * en transit pendant le transport, disponible une fois stocké ou mis en vente.
     */
    public static function statutPourEtape(string $type): string
    {
        return match ($type) {
            'transport' => 'en_transit',
            'stockage', 'distribution' => 'disponible',
            default => 'en_production',
        };
    }

    /**
     * Aligne le statut du lot sur sa dernière étape. Un lot rappelé le reste :
     * seul un changement manuel du statut lève un rappel.
     */
    public function synchroniserStatut(): void
    {
        if ($this->statut === 'rappele') {
            return;
        }

        $derniere = $this->etapes()->reorder()->orderByDesc('date_etape')->orderByDesc('id')->first();

        if ($derniere) {
            $this->update(['statut' => self::statutPourEtape($derniere->type_etape)]);
        }
    }

    /**
     * Le lot a-t-il dépassé sa date de péremption ?
     */
    public function estPerime(): bool
    {
        return $this->date_peremption !== null && $this->date_peremption->isPast() && ! $this->date_peremption->isToday();
    }

    /**
     * Libellé français du statut (accessor « statut_label »).
     */
    protected function statutLabel(): Attribute
    {
        return Attribute::get(fn () => self::STATUTS[$this->statut]['label'] ?? ucfirst((string) $this->statut));
    }

    /**
     * Couleur Bootstrap du badge de statut (accessor « statut_badge »).
     */
    protected function statutBadge(): Attribute
    {
        return Attribute::get(fn () => self::STATUTS[$this->statut]['badge'] ?? 'secondary');
    }

    /**
     * Lots visibles par un utilisateur : tous pour l'admin ; sinon ceux dont le produit
     * lui appartient OU sur lesquels il a réalisé au moins une étape (transformateur, distributeur).
     */
    public function scopePourUtilisateur(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereHas('produit', fn (Builder $produit) => $produit->where('user_id', $user->id))
                ->orWhereHas('etapes', fn (Builder $etape) => $etape->where('user_id', $user->id));
        });
    }

    /**
     * L'utilisateur peut-il consulter ce lot ? (mêmes règles que le scope pourUtilisateur)
     */
    public function estVisiblePar(User $user): bool
    {
        return self::pourUtilisateur($user)->whereKey($this->id)->exists();
    }

    /**
     * Modifier ou supprimer un lot est réservé au propriétaire du produit et à l'admin.
     */
    public function estModifiablePar(User $user): bool
    {
        return $user->isAdmin() || $this->produit?->user_id === $user->id;
    }

    /**
     * L'utilisateur peut-il déclarer une étape sur ce lot ? (mêmes règles que le scope ouvertAuxEtapes)
     */
    public function accepteEtapeDe(User $user): bool
    {
        return self::ouvertAuxEtapes($user)->whereKey($this->id)->exists();
    }

    /**
     * L'utilisateur peut-il rattacher un lot à un lot d'origine (matière première) ?
     * Réservé au transformateur, qui fabrique à partir du lot d'un autre, et à l'admin.
     */
    public static function origineAutoriseePour(User $user): bool
    {
        return $user->isAdmin() || $user->type_acteur === 'transformateur';
    }

    /**
     * Prochain code libre pour l'année en cours : LOT-AAAA-NNNN.
     */
    public static function prochainCode(): string
    {
        $prefixe = 'LOT-'.now()->format('Y').'-';

        $dernier = self::where('code', 'like', $prefixe.'%')->max('code');
        $numero = $dernier ? (int) substr($dernier, -4) + 1 : 1;

        return $prefixe.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lots sur lesquels un utilisateur peut déclarer une étape. Un transformateur ou un
     * distributeur intervient sur les lots d'autres acteurs : il doit pouvoir y ajouter
     * sa première étape, donc tous les lots lui sont ouverts. Le producteur reste limité
     * aux lots qu'il voit déjà.
     */
    public function scopeOuvertAuxEtapes(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || in_array($user->type_acteur, ['transformateur', 'distributeur'], true)) {
            return $query;
        }

        return $query->pourUtilisateur($user);
    }
}
