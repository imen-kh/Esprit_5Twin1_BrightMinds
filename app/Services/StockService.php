<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Harvest;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registre de stock en kilogrammes.
 * Une récolte versée et une commande déduite ne sont jamais comptées une seconde fois.
 * Les lots de traçabilité restent hors de ce calcul.
 */
class StockService
{
    /** @var list<string> */
    public const VENTES_CONFIRMEES = ['confirmee', 'preparee', 'expediee', 'en_transit', 'livree'];

    public function __construct(private HarvestPlannerService $recoltes) {}

    /**
     * @param  array{culture_name: string, product_id: ?int, seuil_kg: float, kg_par_unite: ?float, quantite_initiale: ?float}  $donnees
     */
    public function ouvrir(User $user, array $donnees): StockItem
    {
        return DB::transaction(function () use ($user, $donnees) {
            $fiche = StockItem::query()->create([
                'user_id' => $user->id,
                'product_id' => $donnees['product_id'],
                'culture_name' => $donnees['culture_name'],
                'seuil_kg' => $donnees['seuil_kg'],
                'kg_par_unite' => $donnees['kg_par_unite'],
            ]);

            if (($donnees['quantite_initiale'] ?? 0) > 0) {
                $this->ecrire($fiche, [
                    'type' => StockMovement::ENTREE,
                    'quantite_kg' => $donnees['quantite_initiale'],
                    'source' => StockMovement::MANUEL,
                    'motif' => 'Stock initial',
                    'moved_at' => Carbon::today()->toDateString(),
                ]);
            }

            return $fiche;
        });
    }

    /**
     * @param  array{type: string, quantite_kg: float, motif: ?string, moved_at: string}  $donnees
     */
    public function mouvement(StockItem $fiche, array $donnees): StockMovement
    {
        return DB::transaction(function () use ($fiche, $donnees) {
            $fiche = StockItem::query()->whereKey($fiche->id)->lockForUpdate()->firstOrFail();

            return $this->ecrire($fiche, $donnees + ['source' => StockMovement::MANUEL]);
        });
    }

    public function synchroniser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->retirerVentesAnnulees($user);
            $this->verserRecoltes($user);
            $this->deduireVentesConfirmees($user);
        });
    }

    /**
     * Commandes confirmées encore non déduites : poids d'unité absent, ou stock insuffisant.
     *
     * @return array{sans_poids: int, sans_stock: int}
     */
    public function ventesEnSuspens(StockItem $fiche): array
    {
        if ($fiche->product_id === null) {
            return ['sans_poids' => 0, 'sans_stock' => 0];
        }

        $deja = StockMovement::query()
            ->whereNotNull('commande_produit_id')
            ->pluck('commande_produit_id');

        $lignes = DB::table('commande_produit')
            ->join('commandes', 'commandes.id', '=', 'commande_produit.commande_id')
            ->where('commande_produit.product_id', $fiche->product_id)
            ->whereIn('commandes.statut', self::VENTES_CONFIRMEES)
            ->whereNotIn('commande_produit.id', $deja->all() ?: [0])
            ->get(['commande_produit.quantite']);

        if ($fiche->kg_par_unite === null) {
            return ['sans_poids' => $lignes->count(), 'sans_stock' => 0];
        }

        $disponible = $fiche->disponible();
        $sansStock = $lignes->filter(function ($ligne) use ($fiche, $disponible) {
            return round((float) $ligne->quantite * (float) $fiche->kg_par_unite, 1) > $disponible;
        })->count();

        return ['sans_poids' => 0, 'sans_stock' => $sansStock];
    }

    /**
     * @return array<string, mixed>
     */
    public function prevoir(StockItem $fiche, bool $avecMeteo = true): array
    {
        $fiche->loadMissing('user');
        $stock = $fiche->disponible();
        $sorties = $fiche->movements()
            ->where('type', StockMovement::SORTIE)
            ->whereDate('moved_at', '>=', Carbon::today()->subDays(30))
            ->orderBy('moved_at')
            ->get();

        $suffisant = $sorties->count() >= 2;
        $venteJour = 0.0;

        if ($suffisant) {
            $jours = max(1, (int) round(Carbon::parse($sorties->min('moved_at'))->startOfDay()->diffInDays(Carbon::today()->startOfDay())));
            $venteJour = round((float) $sorties->sum('quantite_kg') / $jours, 2);
        } elseif ($sorties->count() === 1) {
            $venteJour = round((float) $sorties->first()->quantite_kg / 30, 2);
        }

        $aVenir = $this->recoltesAVenir($fiche);
        $projection = function (int $jours) use ($stock, $venteJour, $aVenir): float {
            $apport = (float) $aVenir
                ->filter(fn (Harvest $recolte) => $recolte->planned_harvest_at->between(Carbon::today(), Carbon::today()->addDays($jours)))
                ->sum('estimated_kg');

            return round(max(0, $stock - ($venteJour * $jours) + $apport), 1);
        };

        $rupture = null;
        if ($venteJour > 0) {
            $niveau = $stock;
            for ($jour = 1; $jour <= 30; $jour++) {
                $date = Carbon::today()->addDays($jour);
                $niveau += (float) $aVenir->filter(fn (Harvest $recolte) => $recolte->planned_harvest_at->isSameDay($date))->sum('estimated_kg');
                $niveau -= $venteJour;
                if ($niveau <= 0) {
                    $rupture = $date;
                    break;
                }
            }
        }

        $meteo = $avecMeteo
            ? $this->meteo($fiche->user, $aVenir)
            : ['alertes' => [], 'message' => null];

        return [
            'disponible' => $stock,
            'vente_jour' => $venteJour,
            'suffisant' => $suffisant,
            'message' => $suffisant
                ? 'Rythme calculé sur les sorties des 30 derniers jours.'
                : 'Historique insuffisant. Le rythme éventuel est un calcul simple, pas une prédiction.',
            'j7' => $projection(7),
            'j14' => $projection(14),
            'j30' => $projection(30),
            'rupture' => $rupture,
            'recommandations' => $this->recommandations($fiche, $stock, $rupture, $aVenir, $suffisant),
            'recoltes' => $aVenir,
            'alertes' => $meteo['alertes'],
            'alerte_message' => $meteo['message'],
        ];
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function ecrire(StockItem $fiche, array $donnees): StockMovement
    {
        $quantite = round((float) $donnees['quantite_kg'], 1);

        $disponible = $fiche->disponible();

        if (in_array($donnees['type'], [StockMovement::SORTIE, StockMovement::PERTE], true) && $quantite > $disponible) {
            throw ValidationException::withMessages([
                'quantite_kg' => 'La quantité dépasse le stock disponible ('.Harvest::formaterKg($disponible).' kg).',
            ]);
        }

        return $fiche->movements()->create([
            'type' => $donnees['type'],
            'quantite_kg' => $quantite,
            'source' => $donnees['source'] ?? StockMovement::MANUEL,
            'motif' => $donnees['motif'] ?? null,
            'harvest_id' => $donnees['harvest_id'] ?? null,
            'commande_produit_id' => $donnees['commande_produit_id'] ?? null,
            'moved_at' => $donnees['moved_at'],
        ]);
    }

    private function verserRecoltes(User $user): void
    {
        $recoltes = Harvest::query()
            ->where('user_id', $user->id)
            ->where('status', Harvest::RECOLTEE)
            ->whereNotNull('harvested_kg')
            ->get();

        foreach ($recoltes as $recolte) {
            if (StockMovement::query()->where('harvest_id', $recolte->id)->exists()) {
                continue;
            }

            $fiche = $this->ficheDeRecolte($user, $recolte);

            if ($fiche === null || (float) $recolte->harvested_kg <= 0) {
                continue;
            }

            $fiche = StockItem::query()->whereKey($fiche->id)->lockForUpdate()->firstOrFail();
            $this->ecrire($fiche, [
                'type' => StockMovement::ENTREE,
                'quantite_kg' => $recolte->harvested_kg,
                'source' => StockMovement::RECOLTE,
                'motif' => 'Récolte du '.$recolte->planned_harvest_at->format('d/m/Y'),
                'harvest_id' => $recolte->id,
                'moved_at' => $recolte->planned_harvest_at->toDateString(),
            ]);
        }
    }

    private function deduireVentesConfirmees(User $user): void
    {
        $lignes = DB::table('commande_produit')
            ->join('commandes', 'commandes.id', '=', 'commande_produit.commande_id')
            ->join('products', 'products.id', '=', 'commande_produit.product_id')
            ->where('products.user_id', $user->id)
            ->whereIn('commandes.statut', self::VENTES_CONFIRMEES)
            ->select('commande_produit.*', 'commandes.date_commande')
            ->get();

        foreach ($lignes as $ligne) {
            if (StockMovement::query()->where('commande_produit_id', $ligne->id)->exists()) {
                continue;
            }

            $fiche = StockItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $ligne->product_id)
                ->whereNotNull('kg_par_unite')
                ->lockForUpdate()
                ->first();

            if ($fiche === null) {
                continue;
            }

            $kg = round((float) $ligne->quantite * (float) $fiche->kg_par_unite, 1);

            if ($kg <= 0 || $kg > $fiche->disponible()) {
                continue;
            }

            $this->ecrire($fiche, [
                'type' => StockMovement::SORTIE,
                'quantite_kg' => $kg,
                'source' => StockMovement::COMMANDE,
                'motif' => 'Commande n°'.$ligne->commande_id,
                'commande_produit_id' => $ligne->id,
                'moved_at' => $ligne->date_commande ? Carbon::parse($ligne->date_commande)->toDateString() : Carbon::today()->toDateString(),
            ]);
        }
    }

    private function retirerVentesAnnulees(User $user): void
    {
        $mouvements = StockMovement::query()
            ->whereNotNull('commande_produit_id')
            ->whereIn('stock_item_id', StockItem::query()->where('user_id', $user->id)->pluck('id'))
            ->lockForUpdate()
            ->get();

        foreach ($mouvements as $mouvement) {
            $ligne = DB::table('commande_produit')->where('id', $mouvement->commande_produit_id)->first();
            $statut = $ligne ? Commande::query()->whereKey($ligne->commande_id)->value('statut') : null;

            if (! in_array($statut, self::VENTES_CONFIRMEES, true)) {
                $mouvement->delete();
            }
        }
    }

    private function ficheDeRecolte(User $user, Harvest $recolte): ?StockItem
    {
        return StockItem::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($recolte) {
                $query->where('culture_name', $recolte->nom());
                if ($recolte->product_id) {
                    $query->orWhere('product_id', $recolte->product_id);
                }
            })
            ->first();
    }

    /**
     * @return Collection<int, Harvest>
     */
    private function recoltesAVenir(StockItem $fiche): Collection
    {
        return Harvest::query()
            ->where('user_id', $fiche->user_id)
            ->whereIn('status', [Harvest::PLANIFIEE, Harvest::EN_COURS])
            ->whereDate('planned_harvest_at', '>=', Carbon::today())
            ->whereDate('planned_harvest_at', '<=', Carbon::today()->addDays(30))
            ->where(function ($query) use ($fiche) {
                $query->where('culture_name', $fiche->culture_name);
                if ($fiche->product_id) {
                    $query->orWhere('product_id', $fiche->product_id);
                }
            })
            ->orderBy('planned_harvest_at')
            ->get()
            ->reject(fn (Harvest $recolte) => StockMovement::query()->where('harvest_id', $recolte->id)->exists())
            ->values();
    }

    /**
     * @param  Collection<int, Harvest>  $recoltes
     * @return array{alertes: list<array<string, mixed>>, message: ?string}
     */
    private function meteo(User $user, Collection $recoltes): array
    {
        $proches = $recoltes->filter(fn (Harvest $recolte) => $recolte->planned_harvest_at->between(Carbon::today(), Carbon::today()->addDays(6)));
        $alertes = [];
        $message = null;

        foreach ($proches as $recolte) {
            $risque = $this->recoltes->risques($user, $recolte->planned_harvest_at, $recolte->nom());
            $alertes = array_merge($alertes, $risque['alertes']);
            $message ??= $risque['message'];
        }

        return ['alertes' => $alertes, 'message' => $proches->isEmpty() ? null : $message];
    }

    /**
     * @param  Collection<int, Harvest>  $recoltes
     * @return list<string>
     */
    private function recommandations(StockItem $fiche, float $stock, ?Carbon $rupture, Collection $recoltes, bool $suffisant): array
    {
        $textes = [];

        if ($stock <= 0) {
            $textes[] = 'Rupture : aucune quantité disponible. Enregistrez une entrée ou une récolte.';
        } elseif ($stock <= (float) $fiche->seuil_kg) {
            $textes[] = 'Stock faible : '.Harvest::formaterKg($stock).' kg restent, sous le seuil de '.Harvest::formaterKg($fiche->seuil_kg).' kg.';
        }

        if ($rupture) {
            $textes[] = 'À ce rythme, le stock s’épuise vers le '.$rupture->format('d/m/Y').'. Prévoyez une entrée avant cette date.';
        } elseif (! $suffisant) {
            $textes[] = 'Aucune date de rupture n’est estimée : l’historique des sorties est insuffisant.';
        }

        foreach ($recoltes as $recolte) {
            $textes[] = 'Récolte planifiée de '.Harvest::formaterKg($recolte->estimated_kg).' kg le '.$recolte->planned_harvest_at->format('d/m/Y').'. Elle n’est pas encore dans le stock.';
        }

        return $textes;
    }
}
