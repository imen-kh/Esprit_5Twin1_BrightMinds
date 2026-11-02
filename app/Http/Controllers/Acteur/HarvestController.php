<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\HarvestRequest;
use App\Models\Harvest;
use App\Models\Product;
use App\Services\HarvestPlannerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HarvestController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Harvest::class);

        $recoltes = auth()->user()->harvests()->with('product')->orderBy('planned_harvest_at')->get();

        return view('acteur.recoltes.index', [
            'recoltes' => $recoltes,
            'estime' => $recoltes->sum('estimated_kg'),
            'recolte' => $recoltes->where('status', Harvest::RECOLTEE)->sum('harvested_kg'),
        ]);
    }

    public function events(): JsonResponse
    {
        $this->authorize('viewAny', Harvest::class);

        $evenements = auth()->user()->harvests()->with('product')->get()->map(function (Harvest $recolte) {
            return [
                'id' => $recolte->id,
                'title' => $recolte->nom().' · '.Harvest::formaterKg($recolte->estimated_kg).' kg estimés',
                'start' => $recolte->planned_harvest_at->toDateString(),
                'url' => route('acteur.recoltes.show', $recolte),
                'color' => $recolte->couleurCalendrier(),
            ];
        });

        return response()->json($evenements);
    }

    public function create(): View
    {
        $this->authorize('create', Harvest::class);

        return view('acteur.recoltes.form', [
            'recolte' => new Harvest(['status' => Harvest::PLANIFIEE]),
            'produits' => $this->produits(),
        ]);
    }

    public function store(HarvestRequest $request): RedirectResponse
    {
        $this->authorize('create', Harvest::class);

        $recolte = auth()->user()->harvests()->create($this->donnees($request));

        return redirect()->route('acteur.recoltes.show', $recolte);
    }

    public function show(Harvest $harvest, HarvestPlannerService $planner): View
    {
        $this->authorize('view', $harvest);

        return view('acteur.recoltes.show', [
            'recolte' => $harvest->load('product'),
            'fiche' => $planner->fiche($harvest),
        ]);
    }

    public function edit(Harvest $harvest): View
    {
        $this->authorize('update', $harvest);

        return view('acteur.recoltes.form', [
            'recolte' => $harvest,
            'produits' => $this->produits(),
        ]);
    }

    public function update(HarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $this->authorize('update', $harvest);
        $harvest->update($this->donnees($request));

        return redirect()->route('acteur.recoltes.show', $harvest);
    }

    public function destroy(Harvest $harvest): RedirectResponse
    {
        $this->authorize('delete', $harvest);
        $harvest->delete();

        return redirect()->route('acteur.recoltes.index');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function produits()
    {
        return Product::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function donnees(HarvestRequest $request): array
    {
        $donnees = $request->safe()->except('culture');
        $nom = trim($request->string('culture')->toString());
        $produit = $this->produits()->first(
            fn (Product $produit) => mb_strtolower($produit->name) === mb_strtolower($nom)
        );

        $donnees['culture_name'] = $nom;
        $donnees['product_id'] = $produit?->id;

        if ($donnees['status'] !== Harvest::RECOLTEE) {
            $donnees['harvested_kg'] = null;
        }

        return $donnees;
    }
}
