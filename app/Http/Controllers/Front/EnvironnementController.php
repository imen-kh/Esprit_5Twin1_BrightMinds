<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBilanEnvironnementalRequest;
use App\Http\Requests\UpdateBilanEnvironnementalRequest;
use App\Models\BilanEnvironnemental;
use App\Models\FacteurEmission;
use App\Models\Product;
use App\Services\EnvironmentalScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnvironnementController extends Controller
{
    public function index(Request $request): View
    {
        $query = BilanEnvironnemental::query()->with(['product', 'facteurEmission']);

        if (! $request->user()?->isAdmin()) {
            $query->valide();
        }

        if ($request->filled('eco_score')) {
            $score = (string) $request->string('eco_score');
            if ($score === 'D') {
                $query->whereIn('eco_score', ['D', 'E']);
            } else {
                $query->where('eco_score', $score);
            }
        }

        if ($request->filled('produit')) {
            $query->where('produit_id', $request->integer('produit'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('nom_produit', 'like', $q)
                    ->orWhere('origine_geographique', 'like', $q)
                    ->orWhere('commentaires', 'like', $q)
                    ->orWhereHas('product', function ($product) use ($q) {
                        $product->where('name', 'like', $q);
                    });
            });
        }

        $bilans = $query->latest()->paginate(9)->withQueryString();

        $stats = [
            'total' => BilanEnvironnemental::valide()->count(),
            'nb_a' => BilanEnvironnemental::valide()->where('eco_score', 'A')->count(),
            'nb_b' => BilanEnvironnemental::valide()->where('eco_score', 'B')->count(),
            'nb_c' => BilanEnvironnemental::valide()->where('eco_score', 'C')->count(),
            'co2_moyen' => round(BilanEnvironnemental::valide()->avg('co2_total') ?? 0, 2),
        ];

        return view('front.environnement', compact('bilans', 'stats'));
    }

    public function create(): View
    {
        return view('front.environnement-form', $this->donneesFormulaire(new BilanEnvironnemental([
            'quantite_produit' => 1,
            'unite_mesure' => 'kg',
            'statut' => 'valide',
            'mode_transport' => 'camion',
            'mode_culture' => 'plein_champ',
            'type_emballage' => 'vrac',
            'distance_km' => 35,
            'origine_geographique' => 'Cap Bon, Tunisie',
        ])));
    }

    public function store(StoreBilanEnvironnementalRequest $request, EnvironmentalScoreService $service): RedirectResponse
    {
        $data = $this->synchroniserProduit($request->validated());
        $data = array_merge($data, $service->calculer($data));

        BilanEnvironnemental::query()->create($data);

        return redirect()->route('front.environnement.index')
            ->with('success', 'Bilan environnemental enregistré avec succès.');
    }

    public function edit(BilanEnvironnemental $bilan): View
    {
        $bilan->load('product');

        return view('front.environnement-form', $this->donneesFormulaire($bilan));
    }

    public function update(UpdateBilanEnvironnementalRequest $request, BilanEnvironnemental $bilan, EnvironmentalScoreService $service): RedirectResponse
    {
        $data = $this->synchroniserProduit($request->validated());

        if ($request->boolean('recalculer_ia') || blank($data['score_numerique'] ?? null) || blank($data['eco_score'] ?? null)) {
            $data = array_merge($data, $service->calculer($data));
        }

        $bilan->update($data);

        return redirect()->route('front.environnement.show', $bilan)
            ->with('success', 'Bilan environnemental mis à jour.');
    }

    public function destroy(BilanEnvironnemental $bilan): RedirectResponse
    {
        $bilan->delete();

        return redirect()->route('front.environnement.index')
            ->with('success', 'Bilan environnemental supprimé.');
    }

    public function show(BilanEnvironnemental $bilan): View
    {
        abort_unless($bilan->statut === 'valide' || auth()->user()?->isAdmin(), 404);

        $bilan->load([
            'product',
            'facteurEmission',
            'facteurProduction',
            'facteurTransport',
            'facteurEmballage',
        ]);

        $autresBilans = BilanEnvironnemental::valide()
            ->with(['product', 'facteurEmission'])
            ->where('id', '!=', $bilan->id)
            ->latest()
            ->take(3)
            ->get();

        return view('front.environnement-detail', compact('bilan', 'autresBilans'));
    }

    public function simuler(Request $request, EnvironmentalScoreService $service): JsonResponse
    {
        $resultat = $service->calculer($request->all());

        return response()->json([
            'success' => true,
            'data' => $resultat,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesFormulaire(BilanEnvironnemental $bilan): array
    {
        return [
            'bilan' => $bilan,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'facteurs' => FacteurEmission::query()->orderBy('nom')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function synchroniserProduit(array $data): array
    {
        $product = Product::query()->find($data['produit_id'] ?? null);

        if ($product) {
            $data['nom_produit'] = $product->name;
        }

        return $data;
    }
}
