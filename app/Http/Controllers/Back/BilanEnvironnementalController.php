<?php

namespace App\Http\Controllers\Back;

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

class BilanEnvironnementalController extends Controller
{
    public function index(Request $request): View
    {
        $query = BilanEnvironnemental::query()->with([
            'product',
            'facteurEmission',
            'facteurProduction',
            'facteurTransport',
            'facteurEmballage',
        ]);

        if ($request->filled('eco_score')) {
            $query->where('eco_score', $request->string('eco_score'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('nom_produit', 'like', $q)
                    ->orWhere('origine_geographique', 'like', $q)
                    ->orWhere('alternative_locale', 'like', $q)
                    ->orWhere('commentaires', 'like', $q)
                    ->orWhereHas('product', function ($product) use ($q) {
                        $product->where('name', 'like', $q);
                    });
            });
        }

        $bilans = $query->latest()->paginate(12)->withQueryString();

        // Statistiques globales pour le tableau de bord environnemental
        $stats = [
            'total' => BilanEnvironnemental::count(),
            'score_moyen' => round(BilanEnvironnemental::avg('score_numerique') ?? 0, 1),
            'co2_moyen' => round(BilanEnvironnemental::avg('co2_total') ?? 0, 2),
            'nb_a' => BilanEnvironnemental::where('eco_score', 'A')->count(),
            'nb_b' => BilanEnvironnemental::where('eco_score', 'B')->count(),
            'nb_c' => BilanEnvironnemental::where('eco_score', 'C')->count(),
            'nb_d' => BilanEnvironnemental::where('eco_score', 'D')->count(),
            'nb_e' => BilanEnvironnemental::where('eco_score', 'E')->count(),
        ];

        return view('back.environnement.bilans.index', compact('bilans', 'stats'));
    }

    public function create(): View
    {
        return view('back.environnement.bilans.form', $this->donneesFormulaire(new BilanEnvironnemental([
            'quantite_produit' => 1.0,
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

        return redirect()->route('admin.bilans-environnementaux.index')
            ->with('success', 'Bilan environnemental enregistré avec succès.');
    }

    public function show(BilanEnvironnemental $bilan): View
    {
        $bilan->load([
            'product',
            'facteurEmission',
            'facteurProduction',
            'facteurTransport',
            'facteurEmballage',
        ]);

        return view('back.environnement.bilans.show', compact('bilan'));
    }

    public function edit(BilanEnvironnemental $bilan): View
    {
        return view('back.environnement.bilans.form', $this->donneesFormulaire($bilan));
    }

    public function update(UpdateBilanEnvironnementalRequest $request, BilanEnvironnemental $bilan, EnvironmentalScoreService $service): RedirectResponse
    {
        $data = $this->synchroniserProduit($request->validated());

        if ($request->boolean('recalculer_ia') || blank($data['score_numerique'] ?? null) || blank($data['eco_score'] ?? null)) {
            if ($request->boolean('recalculer_ia')) {
                $data['_reutiliser'] = false;
            }
            $data = array_merge($data, $service->calculer($data));
            unset($data['_reutiliser']);
        }

        $bilan->update($data);

        return redirect()->route('admin.bilans-environnementaux.show', $bilan)
            ->with('success', 'Bilan environnemental mis à jour.');
    }

    public function destroy(BilanEnvironnemental $bilan): RedirectResponse
    {
        $bilan->delete();

        return redirect()->route('admin.bilans-environnementaux.index')
            ->with('success', 'Bilan environnemental supprimé.');
    }

    /**
     * Endpoint API / AJAX pour la fonctionnalité IA en direct :
     * Estimer l'Éco-Score et générer une alternative plus locale.
     */
    public function estimerEcoScoreIA(Request $request, EnvironmentalScoreService $service): JsonResponse
    {
        $params = $request->all();
        $resultat = $service->calculer($params);

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
     * Le nom affiché reprend le produit du catalogue : la colonne nom_produit
     * reste renseignée parce qu'elle est obligatoire dans la table existante.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function synchroniserProduit(array $data): array
    {
        $product = Product::query()->find($data['produit_id']);

        if ($product) {
            $data['nom_produit'] = $product->name;
        }

        return $data;
    }
}
