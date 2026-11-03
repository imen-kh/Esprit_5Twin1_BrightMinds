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
        'motif_rappel',
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
            'histoire_ia_generee_le' => 'datetime',
        ];
    }

    /**
     * Gravité d'une anomalie : libellé affiché et couleur du badge Bootstrap.
     */
    public const GRAVITES = [
        'faible' => ['label' => 'Faible', 'badge' => 'info', 'rang' => 1],
        'moyenne' => ['label' => 'Moyenne', 'badge' => 'warning', 'rang' => 2],
        'elevee' => ['label' => 'Élevée', 'badge' => 'danger', 'rang' => 3],
    ];

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
     * Contrôles automatiques de la chaîne complète, sans IA : trajet impossible, étape manquante,
     * rupture de la chaîne du froid, dates incohérentes et produit « local » venu de trop loin.
     * Les seuils sont dans config/tracabilite.php.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>|null  $etapes  chaîne déjà chargée, sinon chaineComplete()
     * @return list<array{type: string, gravite: string, message: string, etape_id: int|null}>
     */
    public function anomalies(?Collection $etapes = null): array
    {
        $etapes ??= $this->chaineComplete();
        $anomalies = [];

        if ($etapes->isEmpty()) {
            return [];
        }

        // ===== Étapes manquantes : la chaîne doit commencer à la ferme ou à l'usine... =====
        $premiere = $etapes->first();

        if (! in_array($premiere->type_etape, ['production', 'transformation'], true)) {
            $anomalies[] = self::anomalie('etape_manquante', 'moyenne',
                "La chaîne ne commence ni par une production ni par une transformation (première étape : {$premiere->type['label']}).", $premiere->id);
        }

        // ... et finir en point de vente, sauf si le lot est encore en cours, rappelé ou transformé en un autre lot
        $termine = ! in_array($this->statut, ['en_production', 'en_transit', 'rappele'], true);

        if ($termine && $etapes->last()->type_etape !== 'distribution' && ! $this->lotsDerives()->exists()) {
            $anomalies[] = self::anomalie('etape_manquante', 'moyenne',
                "Le lot est déclaré « {$this->statut_label} » mais aucune étape de distribution ne termine la chaîne.", null);
        }

        // ===== Chaîne du froid : température trop haute pendant un transport ou un stockage =====
        $temperatureMax = (float) config('tracabilite.temperature_max', 8);

        foreach ($etapes as $etape) {
            if (in_array($etape->type_etape, ['transport', 'stockage'], true) && $etape->temperature !== null && (float) $etape->temperature > $temperatureMax) {
                $anomalies[] = self::anomalie('temperature', 'elevee',
                    "Rupture de la chaîne du froid : {$etape->temperature_formatee} pendant « {$etape->titre} » (seuil : ".self::nombre($temperatureMax).' °C).', $etape->id);
            }
        }

        // ===== Trajet impossible : vitesse moyenne entre deux étapes géolocalisées successives =====
        $vitesseMax = (float) config('tracabilite.vitesse_max_kmh', 120);
        $points = $etapes->filter(fn (EtapeTracabilite $e) => $e->latitude !== null && $e->longitude !== null)->values();

        for ($i = 1; $i < $points->count(); $i++) {
            [$avant, $apres] = [$points[$i - 1], $points[$i]];
            $distance = self::distanceKm((float) $avant->latitude, (float) $avant->longitude, (float) $apres->latitude, (float) $apres->longitude);
            $heures = $avant->date_etape->diffInMinutes($apres->date_etape) / 60;

            // Moins d'1 km : même site, aucune vitesse à contrôler
            if ($distance >= 1 && ($heures <= 0 || $distance / $heures > $vitesseMax)) {
                $minutes = (int) round($heures * 60);
                $duree = $minutes < 60 ? "{$minutes} min" : intdiv($minutes, 60).' h'.($minutes % 60 ? ' '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : '');
                $vitesse = $heures > 0 ? ' (≈ '.self::nombre(round($distance / $heures)).' km/h)' : '';
                $anomalies[] = self::anomalie('vitesse', 'elevee',
                    'Trajet impossible : '.self::nombre(round($distance))." km en {$duree} entre {$avant->lieu} et {$apres->lieu}{$vitesse}, au-delà de ".self::nombre($vitesseMax).' km/h.', $apres->id);
            }
        }

        // ===== Dates incohérentes =====
        if ($this->date_peremption && $this->date_peremption->lt($this->date_production)) {
            $anomalies[] = self::anomalie('dates', 'elevee', 'La date de péremption est antérieure à la date de production.', null);
        }

        $distributionVue = [];

        foreach ($etapes as $etape) {
            $lotEtape = $etape->lot;

            if ($lotEtape && $etape->date_etape->lt($lotEtape->date_production->copy()->startOfDay())) {
                $anomalies[] = self::anomalie('dates', 'moyenne',
                    "L'étape « {$etape->titre} » ({$etape->date_etape->format('d/m/Y')}) précède la production du lot {$lotEtape->code} ({$lotEtape->date_production->format('d/m/Y')}).", $etape->id);
            }

            if ($etape->date_etape->isFuture()) {
                $anomalies[] = self::anomalie('dates', 'moyenne', "L'étape « {$etape->titre} » est datée dans le futur ({$etape->date_etape->format('d/m/Y H:i')}).", $etape->id);
            }

            if ($lotEtape && $etape->type_etape === 'distribution' && $lotEtape->date_peremption && $etape->date_etape->gt($lotEtape->date_peremption->copy()->endOfDay())) {
                $anomalies[] = self::anomalie('dates', 'elevee', "Le lot {$lotEtape->code} a été mis en vente après sa date de péremption.", $etape->id);
            }

            // Une production ou une transformation après la mise en vente du même lot n'a pas de sens
            if (in_array($etape->type_etape, ['production', 'transformation'], true) && isset($distributionVue[$etape->lot_id])) {
                $anomalies[] = self::anomalie('dates', 'moyenne', "L'étape « {$etape->titre} » ({$etape->type['label']}) a lieu après la distribution du lot.", $etape->id);
            }

            if ($etape->type_etape === 'distribution') {
                $distributionVue[$etape->lot_id] = true;
            }
        }

        // ===== Produit annoncé « local » mais venu de loin =====
        $produit = $this->produit;
        $annonceLocal = $produit && preg_match('/\blocal(e|es|aux)?\b/iu', $produit->name.' '.$produit->description.' '.$produit->origin);
        $distanceLocaleMax = (int) config('tracabilite.distance_locale_max_km', 300);

        if ($annonceLocal) {
            $distanceTotale = $this->statistiquesChaine($etapes)['distance_km'];

            if ($distanceTotale > $distanceLocaleMax) {
                $anomalies[] = self::anomalie('local', 'moyenne',
                    "Produit présenté comme local, mais la chaîne parcourt {$distanceTotale} km (limite : {$distanceLocaleMax} km).", null);
            }
        }

        return $anomalies;
    }

    /**
     * Gravité la plus haute d'une liste d'anomalies (clé de GRAVITES), ou null si la liste est vide.
     *
     * @param  list<array{gravite: string}>  $anomalies
     */
    public static function graviteMax(array $anomalies): ?string
    {
        return collect($anomalies)->sortByDesc(fn (array $a) => self::GRAVITES[$a['gravite']]['rang'] ?? 0)->first()['gravite'] ?? null;
    }

    /**
     * Le lot est-il un produit transformé ? (lot d'origine, produit transformé ou étape de transformation)
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>|null  $etapes
     */
    public function estTransforme(?Collection $etapes = null): bool
    {
        $etapes ??= $this->chaineComplete();

        return $this->lot_origine_id !== null
            || $this->produit?->type_produit === 'transforme'
            || $etapes->where('lot_id', $this->id)->contains('type_etape', 'transformation');
    }

    /**
     * Détail du score de transparence : chaque critère avec ses points obtenus et son maximum.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>|null  $etapes
     * @param  list<array<string, mixed>>|null  $anomalies
     * @return list<array{libelle: string, points: int, max: int}>
     */
    public function criteresTransparence(?Collection $etapes = null, ?array $anomalies = null): array
    {
        $etapes ??= $this->chaineComplete();
        $anomalies ??= $this->anomalies($etapes);
        $total = max(1, $etapes->count());

        $debutOk = $etapes->isNotEmpty() && in_array($etapes->first()->type_etape, ['production', 'transformation'], true);
        // Un lot de matière première transformé en un autre lot poursuit sa chaîne dans le lot dérivé
        $finOk = $etapes->isNotEmpty() && ($etapes->last()->type_etape === 'distribution' || $this->lotsDerives()->exists());
        $avecGps = $etapes->filter(fn (EtapeTracabilite $e) => $e->latitude !== null && $e->longitude !== null)->count();
        $avecTemperature = $etapes->filter(fn (EtapeTracabilite $e) => $e->temperature !== null)->count();
        $vide = $etapes->isEmpty();

        return [
            ['libelle' => 'Chaîne complète, de la production à la distribution (ou à la transformation)', 'points' => ($debutOk ? 20 : 0) + ($finOk ? 20 : 0), 'max' => 40],
            ['libelle' => 'Coordonnées GPS renseignées', 'points' => $vide ? 0 : (int) round(20 * $avecGps / $total), 'max' => 20],
            ['libelle' => 'Températures renseignées', 'points' => $vide ? 0 : (int) round(20 * $avecTemperature / $total), 'max' => 20],
            // Un produit brut n'a pas de lot d'origine : le critère est acquis
            ['libelle' => $this->estTransforme($etapes) ? 'Lot de matière première connu' : 'Produit brut (pas de matière première à tracer)',
                'points' => ! $this->estTransforme($etapes) || $this->lot_origine_id ? 10 : 0, 'max' => 10],
            ['libelle' => 'Aucune anomalie détectée', 'points' => $anomalies === [] && ! $vide ? 10 : 0, 'max' => 10],
        ];
    }

    /**
     * Score de transparence de 0 à 100 (somme des critères de criteresTransparence()).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\EtapeTracabilite>|null  $etapes
     * @param  list<array<string, mixed>>|null  $anomalies
     */
    public function scoreTransparence(?Collection $etapes = null, ?array $anomalies = null): int
    {
        return (int) array_sum(array_column($this->criteresTransparence($etapes, $anomalies), 'points'));
    }

    /**
     * Couleur Bootstrap d'un score de transparence : vert, orange ou rouge.
     */
    public static function couleurScore(int $score): string
    {
        return $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
    }

    /**
     * Date de la dernière modification de la chaîne : lots de la lignée et leurs étapes.
     */
    public function derniereModification(): ?\Illuminate\Support\Carbon
    {
        $lignee = $this->lignee();

        $dates = $lignee->pluck('updated_at')
            ->merge(EtapeTracabilite::whereIn('lot_id', $lignee->pluck('id'))->pluck('updated_at'))
            ->filter();

        return $dates->max();
    }

    /**
     * L'histoire IA enregistrée est-elle encore valable ? (générée après la dernière modification)
     */
    public function histoireAJour(): bool
    {
        if (! $this->histoire_ia || ! $this->histoire_ia_generee_le) {
            return false;
        }

        $modification = $this->derniereModification();

        return $modification === null || $this->histoire_ia_generee_le->gte($modification);
    }

    /**
     * Construit une anomalie au format commun.
     *
     * @return array{type: string, gravite: string, message: string, etape_id: int|null}
     */
    private static function anomalie(string $type, string $gravite, string $message, ?int $etapeId): array
    {
        return ['type' => $type, 'gravite' => $gravite, 'message' => $message, 'etape_id' => $etapeId];
    }

    /**
     * Nombre à la française, sans décimales inutiles (8 et non 8,0 ; 1,5).
     */
    private static function nombre(float|int $valeur): string
    {
        return rtrim(rtrim(number_format((float) $valeur, 1, ',', ' '), '0'), ',');
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
     * Nombre de jours avant la péremption (0 = aujourd'hui, négatif = dépassée), ou null sans date.
     */
    public function joursAvantPeremption(): ?int
    {
        return $this->date_peremption === null ? null : (int) today()->diffInDays($this->date_peremption, false);
    }

    /**
     * Alerte de péremption à afficher : lot périmé (rouge) ou bientôt périmé (orange, délai dans
     * config/tracabilite.php). null si la date est lointaine ou absente, ou si le lot est rappelé.
     *
     * @return array{niveau: string, titre: string, message: string}|null
     */
    public function alertePeremption(): ?array
    {
        $jours = $this->joursAvantPeremption();

        if ($jours === null || $this->statut === 'rappele') {
            return null;
        }

        $date = $this->date_peremption->format('d/m/Y');

        if ($jours < 0) {
            return ['niveau' => 'danger', 'titre' => 'Produit périmé',
                'message' => "La date de péremption de ce lot est dépassée depuis le {$date} : ne le consommez pas."];
        }

        if ($jours <= (int) config('tracabilite.peremption_alerte_jours', 3)) {
            $echeance = match ($jours) {
                0 => 'aujourd\'hui',
                1 => 'demain',
                default => "dans {$jours} jours",
            };

            return ['niveau' => 'warning', 'titre' => 'Bientôt périmé',
                'message' => "Ce lot arrive à péremption {$echeance} ({$date}) : à consommer rapidement."];
        }

        return null;
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
