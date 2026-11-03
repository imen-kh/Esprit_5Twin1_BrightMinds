<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs agricoles calculés en base.
 * Les résumés passent ensuite par DashboardInsightsService, qui ne crée pas de chiffres.
 */
class DashboardAgricoleService
{
    public function __construct(
        private StockService $stocks,
        private AgriWeatherService $meteo,
        private HarvestPlannerService $recoltes,
        private DashboardInsightsService $insights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function producteur(User $user): array
    {
        $this->stocks->synchroniser($user);

        $produits = Product::query()->where('user_id', $user->id)->orderBy('name')->get();
        $fiches = $user->stockItems()->orderBy('culture_name')->get();
        $previsions = $fiches->map(fn (StockItem $fiche) => [
            'fiche' => $fiche,
            'prevision' => $this->stocks->prevoir($fiche, false),
        ]);
        $recoltes = Harvest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Harvest::PLANIFIEE, Harvest::EN_COURS])
            ->whereDate('planned_harvest_at', '>=', Carbon::today())
            ->orderBy('planned_harvest_at')
            ->get();
        $ventes = $this->ventes($user);
        $meteo = $this->meteoProducteur($user, $produits);
        $alertes = $this->alertesProducteur($previsions, $meteo['alertes']);
        $courbe = [
            round((float) $previsions->sum(fn (array $ligne) => $ligne['prevision']['disponible']), 1),
            round((float) $previsions->sum(fn (array $ligne) => $ligne['prevision']['j7']), 1),
            round((float) $previsions->sum(fn (array $ligne) => $ligne['prevision']['j14']), 1),
            round((float) $previsions->sum(fn (array $ligne) => $ligne['prevision']['j30']), 1),
        ];
        $ruptures = $previsions
            ->filter(fn (array $ligne) => $ligne['prevision']['rupture'] instanceof Carbon)
            ->map(fn (array $ligne) => [
                'culture' => $ligne['fiche']->culture_name,
                'date' => $ligne['prevision']['rupture']->format('d/m/Y'),
            ])
            ->values()
            ->all();

        $faits = [
            'Produits' => $produits->count(),
            'Stock disponible (kg)' => Harvest::formaterKg($courbe[0]),
            'Récoltes prévues' => $recoltes->count(),
            'Quantité prévue (kg)' => Harvest::formaterKg($recoltes->sum('estimated_kg')),
            'Ventes confirmées' => $ventes['commandes'],
            'Montant des ventes (DT)' => number_format($ventes['montant'], 3, ',', ' '),
            'Alertes actives' => count($alertes),
        ];
        $priorites = $this->prioritesProducteur($alertes, $recoltes, $ruptures);

        return [
            'kpi' => [
                'produits' => $produits->count(),
                'stock_kg' => $courbe[0],
                'recoltes' => $recoltes->count(),
                'recoltes_kg' => round((float) $recoltes->sum('estimated_kg'), 1),
                'ventes' => $ventes['commandes'],
                'ventes_dt' => $ventes['montant'],
                'alertes' => count($alertes),
            ],
            'recoltes' => $recoltes->take(6),
            'courbe' => $courbe,
            'suivi' => $fiches->isNotEmpty(),
            'message_stock' => $previsions->contains(fn (array $ligne) => $ligne['prevision']['suffisant'])
                ? 'Rythme calculé sur les sorties des 30 derniers jours.'
                : 'Historique insuffisant pour une partie des cultures. Aucune rupture n’est inventée sans sorties.',
            'ruptures' => $ruptures,
            'meteo' => $meteo,
            'alertes' => $alertes,
            'insights' => $this->insights->resumer('Exploitation de '.$user->name.'.', $faits, $priorites),
        ];
    }

    /**
     * @param  array{du: ?string, au: ?string, produit: ?string, region: ?string}  $filtres
     * @return array<string, mixed>
     */
    public function admin(array $filtres): array
    {
        $producteurs = User::query()
            ->where('type_acteur', 'producteur')
            ->when($filtres['region'], fn ($query, $region) => $query->where('ville', $region))
            ->get();
        $ids = $producteurs->pluck('id');

        $actifs = $producteurs->filter(function (User $user) {
            return $user->products()->exists() || $user->harvests()->exists() || $user->stockItems()->exists();
        })->count();

        $produits = Product::query()
            ->whereIn('user_id', $ids->all() ?: [0])
            ->when($filtres['produit'], fn ($query, $nom) => $query->where('name', $nom))
            ->when($filtres['du'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filtres['au'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        $attenteRequete = Product::query()
            ->where('status', Product::PENDING)
            ->whereIn('user_id', $ids->all() ?: [0])
            ->when($filtres['produit'], fn ($query, $nom) => $query->where('name', $nom));
        $attenteTotal = (clone $attenteRequete)->count();
        $attente = (clone $attenteRequete)->with(['user', 'category'])->orderBy('created_at')->limit(8)->get();

        $fiches = StockItem::query()
            ->with('user')
            ->whereIn('user_id', $ids->all() ?: [0])
            ->when($filtres['produit'], fn ($query, $nom) => $query->where('culture_name', $nom))
            ->orderBy('culture_name')
            ->get();
        $stocks = $fiches->map(function (StockItem $fiche) {
            return [
                'fiche' => $fiche,
                'region' => $fiche->user->ville ?: 'Non renseignée',
                'disponible' => $fiche->disponible(),
                'niveau' => $fiche->niveau(),
            ];
        });
        $critiques = $stocks->whereIn('niveau', ['rupture', 'faible'])->values();

        $recoltes = Harvest::query()
            ->with('user')
            ->whereIn('status', [Harvest::PLANIFIEE, Harvest::EN_COURS])
            ->whereIn('user_id', $ids->all() ?: [0])
            ->when($filtres['produit'], function ($query, $nom) {
                $query->where(function ($filtre) use ($nom) {
                    $filtre->where('culture_name', $nom)
                        ->orWhereHas('product', fn ($produit) => $produit->where('name', $nom));
                });
            })
            ->when($filtres['du'], fn ($query, $date) => $query->whereDate('planned_harvest_at', '>=', $date))
            ->when($filtres['au'], fn ($query, $date) => $query->whereDate('planned_harvest_at', '<=', $date))
            ->orderBy('planned_harvest_at')
            ->get();

        $parCategorie = (clone $produits)
            ->where('status', Product::PUBLISHED)
            ->with('category')
            ->get()
            ->groupBy(fn (Product $produit) => $produit->category->name ?? 'Sans catégorie');
        $parRegionStock = $stocks->groupBy('region');
        $parRegionRecolte = $recoltes->groupBy(fn (Harvest $recolte) => $recolte->user->ville ?: 'Non renseignée');
        $alertes = $this->alertesAdmin($critiques, $recoltes, $ids);

        $faits = [
            'Producteurs actifs' => $actifs,
            'Produits publiés' => (clone $produits)->where('status', Product::PUBLISHED)->count(),
            'Produits à valider' => $attenteTotal,
            'Stocks critiques' => $critiques->count(),
            'Récoltes prévues' => $recoltes->count(),
            'Quantité prévue (kg)' => Harvest::formaterKg($recoltes->sum('estimated_kg')),
            'Alertes' => count($alertes),
        ];
        $priorites = collect($alertes)->pluck('titre')->take(5)->values()->all();
        if ($priorites === [] && $attente->isNotEmpty()) {
            $priorites[] = $attente->count().' produit(s) attendent une validation.';
        }

        return [
            'kpi' => $faits,
            'attente' => $attente,
            'critiques' => $critiques,
            'recoltes' => $recoltes->take(8),
            'alertes' => $alertes,
            'graphiques' => [
                'categories' => $parCategorie->keys()->values()->all(),
                'produits' => $parCategorie->map(fn (Collection $groupe) => $groupe->count())->values()->all(),
                'regions' => $parRegionStock->keys()->values()->all(),
                'stocks' => $parRegionStock->map(fn (Collection $groupe) => round((float) $groupe->sum('disponible'), 1))->values()->all(),
                'regionsRecoltes' => $parRegionRecolte->keys()->values()->all(),
                'recoltes' => $parRegionRecolte->map(fn (Collection $groupe) => round((float) $groupe->sum('estimated_kg'), 1))->values()->all(),
            ],
            'options' => [
                'produits' => Product::query()->whereIn('user_id', User::query()->where('type_acteur', 'producteur')->pluck('id'))->orderBy('name')->pluck('name')->unique()->values(),
                'regions' => User::query()->where('type_acteur', 'producteur')->whereNotNull('ville')->orderBy('ville')->pluck('ville')->unique()->values(),
            ],
            'insights' => $this->insights->resumer('Activité agricole de la plateforme.', $faits, $priorites),
        ];
    }

    /**
     * @return array{commandes: int, montant: float}
     */
    private function ventes(User $user): array
    {
        $ligne = DB::table('commande_produit')
            ->join('commandes', 'commandes.id', '=', 'commande_produit.commande_id')
            ->join('products', 'products.id', '=', 'commande_produit.product_id')
            ->where('products.user_id', $user->id)
            ->whereIn('commandes.statut', StockService::VENTES_CONFIRMEES)
            ->selectRaw('count(distinct commandes.id) as commandes, coalesce(sum(commande_produit.sous_total), 0) as montant')
            ->first();

        return [
            'commandes' => (int) ($ligne->commandes ?? 0),
            'montant' => round((float) ($ligne->montant ?? 0), 3),
        ];
    }

    /**
     * @param  Collection<int, Product>  $produits
     * @return array<string, mixed>
     */
    private function meteoProducteur(User $user, Collection $produits): array
    {
        if (! $this->meteo->aUnLieu($user)) {
            return $this->meteoVide('Indiquez votre ville ou vos coordonnées GPS dans votre profil pour afficher la météo.');
        }

        $lieu = $this->meteo->localiser($user);

        if ($lieu === null) {
            return $this->meteoVide('La météo est indisponible pour le moment.');
        }

        $previsions = $this->meteo->previsions($lieu['latitude'], $lieu['longitude']);

        if ($previsions === null) {
            return $this->meteoVide('La météo est indisponible pour le moment.');
        }

        $noms = $produits->where('type_produit', 'matiere_premiere')->pluck('name')->all();

        return [
            'disponible' => true,
            'message' => null,
            'lieu' => $lieu['nom'],
            'temperature' => $previsions['actuelle']['temperature'],
            'humidite' => $previsions['actuelle']['humidite'],
            'vent' => $previsions['actuelle']['vent'],
            'libelle' => $previsions['actuelle']['libelle'],
            'jours' => $previsions['jours'],
            'alertes' => $this->meteo->alertes($previsions, $this->meteo->seuilsPour($noms)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meteoVide(string $message): array
    {
        return [
            'disponible' => false,
            'message' => $message,
            'lieu' => null,
            'temperature' => null,
            'humidite' => null,
            'vent' => null,
            'libelle' => null,
            'jours' => [],
            'alertes' => [],
        ];
    }

    /**
     * @param  Collection<int, array{fiche: StockItem, prevision: array<string, mixed>}>  $previsions
     * @param  list<array<string, mixed>>  $meteo
     * @return list<array{niveau: string, titre: string, detail: string}>
     */
    private function alertesProducteur(Collection $previsions, array $meteo): array
    {
        $alertes = [];

        foreach ($previsions as $ligne) {
            $niveau = $ligne['fiche']->niveau();
            if (! in_array($niveau, ['rupture', 'faible'], true)) {
                continue;
            }
            $kg = Harvest::formaterKg($ligne['prevision']['disponible']);
            $alertes[] = [
                'niveau' => $niveau === 'rupture' ? 'danger' : 'warning',
                'titre' => $niveau === 'rupture'
                    ? 'Rupture de '.$ligne['fiche']->culture_name
                    : 'Stock faible de '.$ligne['fiche']->culture_name,
                'detail' => $kg.' kg disponibles, seuil '.$ligne['fiche']->seuil_kg.' kg.',
            ];
        }

        foreach ($meteo as $alerte) {
            $alertes[] = [
                'niveau' => $alerte['classe'] === 'danger' ? 'danger' : 'warning',
                'titre' => $alerte['titre'],
                'detail' => $alerte['detail'],
            ];
        }

        return $alertes;
    }

    /**
     * @param  Collection<int, Harvest>  $recoltes
     * @param  list<array{culture: string, date: string}>  $ruptures
     * @return list<string>
     */
    private function prioritesProducteur(array $alertes, Collection $recoltes, array $ruptures): array
    {
        $priorites = collect($alertes)->pluck('titre')->take(4)->all();

        $prochaine = $recoltes->first();
        if ($prochaine) {
            $priorites[] = 'Récolte de '.$prochaine->nom().' prévue le '.$prochaine->planned_harvest_at->format('d/m/Y').', '.Harvest::formaterKg($prochaine->estimated_kg).' kg estimés.';
        }

        foreach (array_slice($ruptures, 0, 2) as $rupture) {
            $priorites[] = 'Rupture estimée pour '.$rupture['culture'].' le '.$rupture['date'].'.';
        }

        return array_values(array_unique($priorites));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $critiques
     * @param  Collection<int, Harvest>  $recoltes
     * @param  Collection<int, int>  $ids
     * @return list<array{niveau: string, titre: string, detail: string}>
     */
    private function alertesAdmin(Collection $critiques, Collection $recoltes, Collection $ids): array
    {
        $alertes = [];

        foreach ($critiques as $ligne) {
            $alertes[] = [
                'niveau' => $ligne['niveau'] === 'rupture' ? 'danger' : 'warning',
                'titre' => ($ligne['niveau'] === 'rupture' ? 'Rupture' : 'Stock faible').' — '.$ligne['fiche']->culture_name,
                'detail' => Harvest::formaterKg($ligne['disponible']).' kg à '.$ligne['region'].'.',
            ];
        }

        $proches = $recoltes->filter(fn (Harvest $recolte) => $recolte->planned_harvest_at->between(Carbon::today(), Carbon::today()->addDays(6)));

        foreach ($proches->take(12) as $recolte) {
            $risque = $this->recoltes->risques($recolte->user, $recolte->planned_harvest_at, $recolte->nom());
            foreach ($risque['alertes'] as $alerte) {
                $alertes[] = [
                    'niveau' => $alerte['classe'] === 'danger' ? 'danger' : 'warning',
                    'titre' => $alerte['titre'].' — '.$recolte->nom(),
                    'detail' => $alerte['detail'].' ('.($recolte->user->ville ?: 'région non renseignée').').',
                ];
            }
        }

        $analyses = Product::query()
            ->whereIn('user_id', $ids->all() ?: [0])
            ->whereNotNull('ai_analysis')
            ->latest('reviewed_at')
            ->limit(20)
            ->get();

        foreach ($analyses as $produit) {
            $anomalies = $produit->ai_analysis['anomalies'] ?? [];
            if (! is_array($anomalies) || $anomalies === []) {
                continue;
            }
            $alertes[] = [
                'niveau' => 'warning',
                'titre' => 'Anomalie signalée — '.$produit->name,
                'detail' => (string) $anomalies[0],
            ];
        }

        $rappels = Lot::query()
            ->with('produit')
            ->where('statut', 'rappele')
            ->whereHas('produit', fn ($query) => $query->whereIn('user_id', $ids->all() ?: [0]))
            ->limit(5)
            ->get();

        foreach ($rappels as $lot) {
            $alertes[] = [
                'niveau' => 'danger',
                'titre' => 'Lot rappelé '.$lot->code,
                'detail' => $lot->produit->name,
            ];
        }

        return $alertes;
    }
}
