<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\StockItem;
use App\Services\HarvestPlannerService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private StockService $stocks,
        private HarvestPlannerService $recoltes,
    ) {}

    public function index(Request $request): View
    {
        $lignes = StockItem::query()
            ->with('user')
            ->orderBy('culture_name')
            ->get()
            ->map(function (StockItem $fiche) {
                $prevision = $this->stocks->prevoir($fiche, false);

                return [
                    'fiche' => $fiche,
                    'region' => $fiche->user->ville ?: 'Non renseignée',
                    'disponible' => $prevision['disponible'],
                    'niveau' => $fiche->niveau(),
                    'j7' => $prevision['j7'],
                    'j14' => $prevision['j14'],
                    'j30' => $prevision['j30'],
                    'rupture' => $prevision['rupture'],
                ];
            });

        $culture = trim((string) $request->query('produit', ''));
        $region = trim((string) $request->query('region', ''));
        $niveau = (string) $request->query('niveau', '');

        $filtre = $lignes
            ->when($culture !== '', fn ($liste) => $liste->filter(fn (array $ligne) => $ligne['fiche']->culture_name === $culture))
            ->when($region !== '', fn ($liste) => $liste->filter(fn (array $ligne) => $ligne['region'] === $region))
            ->when(in_array($niveau, ['rupture', 'faible', 'ok'], true), fn ($liste) => $liste->filter(fn (array $ligne) => $ligne['niveau'] === $niveau))
            ->values();

        $parProduit = $filtre->groupBy(fn (array $ligne) => $ligne['fiche']->culture_name);
        $parRegion = $filtre->groupBy('region');

        return view('admin.stocks.index', [
            'lignes' => $filtre,
            'produits' => $lignes->map(fn (array $ligne) => $ligne['fiche']->culture_name)->unique()->sort()->values(),
            'regions' => $lignes->pluck('region')->unique()->sort()->values(),
            'stats' => [
                'fiches' => $filtre->count(),
                'kg' => round((float) $filtre->sum('disponible'), 1),
                'rupture' => $filtre->where('niveau', 'rupture')->count(),
                'faible' => $filtre->where('niveau', 'faible')->count(),
            ],
            'graphiques' => [
                'produits' => $parProduit->keys()->values(),
                'disponible' => $parProduit->map(fn ($groupe) => round((float) $groupe->sum('disponible'), 1))->values(),
                'j7' => $parProduit->map(fn ($groupe) => round((float) $groupe->sum('j7'), 1))->values(),
                'regions' => $parRegion->keys()->values(),
                'j7Region' => $parRegion->map(fn ($groupe) => round((float) $groupe->sum('j7'), 1))->values(),
            ],
            'alertes' => $this->alertesMeteo(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function alertesMeteo(): array
    {
        $recoltes = Harvest::query()
            ->with('user')
            ->whereIn('status', [Harvest::PLANIFIEE, Harvest::EN_COURS])
            ->whereDate('planned_harvest_at', '>=', Carbon::today())
            ->whereDate('planned_harvest_at', '<=', Carbon::today()->addDays(6))
            ->orderBy('planned_harvest_at')
            ->limit(30)
            ->get();

        $alertes = [];

        foreach ($recoltes as $recolte) {
            $risque = $this->recoltes->risques($recolte->user, $recolte->planned_harvest_at, $recolte->nom());

            foreach ($risque['alertes'] as $alerte) {
                $alertes[] = $alerte + [
                    'culture' => $recolte->nom(),
                    'region' => $recolte->user->ville ?: 'Non renseignée',
                ];
            }
        }

        return $alertes;
    }
}
