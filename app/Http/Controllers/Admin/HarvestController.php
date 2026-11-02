<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\User;
use App\Services\HarvestPlannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HarvestController extends Controller
{
    public function index(Request $request, HarvestPlannerService $planner): View
    {
        $debut = $request->date('du');
        $fin = $request->date('au');

        $recoltes = Harvest::query()
            ->with(['product', 'user'])
            ->when($request->filled('produit'), function ($query) use ($request) {
                $nom = $request->string('produit')->toString();
                $query->where(function ($query) use ($nom) {
                    $query->where('culture_name', $nom)
                        ->orWhereHas('product', fn ($product) => $product->where('name', $nom));
                });
            })
            ->when($request->filled('region'), function ($query) use ($request) {
                $query->whereHas('user', fn ($user) => $user->where('ville', $request->string('region')));
            })
            ->when($debut, fn ($query) => $query->whereDate('planned_harvest_at', '>=', $debut))
            ->when($fin, fn ($query) => $query->whereDate('planned_harvest_at', '<=', $fin))
            ->orderBy('planned_harvest_at')
            ->get();

        $horizon = Carbon::today()->addDays(6);
        $alertes = $recoltes
            ->filter(fn (Harvest $recolte) => $recolte->planned_harvest_at->between(Carbon::today(), $horizon))
            ->mapWithKeys(fn (Harvest $recolte) => [
                $recolte->id => $planner->risques($recolte->user, $recolte->planned_harvest_at, $recolte->nom()),
            ]);

        $parProduit = $recoltes->groupBy(fn (Harvest $recolte) => $recolte->nom());
        $parRegion = $recoltes->groupBy(fn (Harvest $recolte) => $recolte->user->ville ?: 'Non renseignée');
        $parMois = $recoltes->groupBy(fn (Harvest $recolte) => $recolte->planned_harvest_at->format('m/Y'));

        return view('admin.recoltes.index', [
            'recoltes' => $recoltes,
            'alertes' => $alertes,
            'regions' => User::query()->whereHas('harvests')->whereNotNull('ville')->distinct()->orderBy('ville')->pluck('ville'),
            'produits' => $recoltes->map(fn (Harvest $recolte) => $recolte->nom())->merge(
                Harvest::query()->with('product')->get()->map(fn (Harvest $recolte) => $recolte->nom())
            )->filter()->unique()->sort()->values(),
            'graphiques' => [
                'produits' => $parProduit->keys()->values(),
                'estimeProduit' => $parProduit->map(fn ($groupe) => round((float) $groupe->sum('estimated_kg'), 1))->values(),
                'recolteProduit' => $parProduit->map(fn ($groupe) => round((float) $groupe->sum('harvested_kg'), 1))->values(),
                'regions' => $parRegion->keys()->values(),
                'estimeRegion' => $parRegion->map(fn ($groupe) => round((float) $groupe->sum('estimated_kg'), 1))->values(),
                'mois' => $parMois->keys()->values(),
                'estimeMois' => $parMois->map(fn ($groupe) => round((float) $groupe->sum('estimated_kg'), 1))->values(),
            ],
        ]);
    }
}
