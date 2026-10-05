<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBilanEnvironnementalRequest;
use App\Models\BilanEnvironnemental;
use App\Models\FacteurEmission;
use App\Services\EcoScoreIAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BilanEnvironnementalController extends Controller
{
    public function index(Request $request): View
    {
        $query = BilanEnvironnemental::query()->with([
            'facteurPrincipal',
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
                    ->orWhere('alternative_locale', 'like', $q);
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
        $facteursProduction = FacteurEmission::actif()->where('categorie', 'production')->orderBy('nom')->get();
        $facteursTransport = FacteurEmission::actif()->where('categorie', 'transport')->orderBy('nom')->get();
        $facteursEmballage = FacteurEmission::actif()->where('categorie', 'emballage')->orderBy('nom')->get();
        $tousFacteurs = FacteurEmission::actif()->orderBy('nom')->get();

        return view('back.environnement.bilans.form', [
            'bilan' => new BilanEnvironnemental([
                'quantite_produit' => 1.0,
                'unite_mesure' => 'kg',
                'statut' => 'valide',
                'mode_transport' => 'camion',
                'mode_culture' => 'plein_champ',
                'type_emballage' => 'vrac',
                'distance_km' => 35,
                'origine_geographique' => 'Cap Bon, Tunisie',
            ]),
            'facteursProduction' => $facteursProduction,
            'facteursTransport' => $facteursTransport,
            'facteursEmballage' => $facteursEmballage,
            'tousFacteurs' => $tousFacteurs,
        ]);
    }

    public function store(StoreBilanEnvironnementalRequest $request, EcoScoreIAService $service): RedirectResponse
    {
        $data = $request->validated();

        // Si le calcul ou l'IA n'ont pas été remplis manuellement, calculer automatiquement
        if (empty($data['co2_total']) || empty($data['eco_score']) || empty($data['alternative_locale'])) {
            $calculs = $service->analyser($data);
            $data = array_merge($data, $calculs);
        }

        $bilan = BilanEnvironnemental::query()->create($data);

        return redirect()->route('admin.bilans-environnementaux.show', $bilan)
            ->with('success', 'Bilan environnemental calculé et enregistré avec succès.');
    }

    public function show(BilanEnvironnemental $bilan): View
    {
        $bilan->load([
            'facteurPrincipal',
            'facteurProduction',
            'facteurTransport',
            'facteurEmballage',
        ]);

        return view('back.environnement.bilans.show', compact('bilan'));
    }

    public function edit(BilanEnvironnemental $bilan): View
    {
        $facteursProduction = FacteurEmission::actif()->where('categorie', 'production')->orderBy('nom')->get();
        $facteursTransport = FacteurEmission::actif()->where('categorie', 'transport')->orderBy('nom')->get();
        $facteursEmballage = FacteurEmission::actif()->where('categorie', 'emballage')->orderBy('nom')->get();
        $tousFacteurs = FacteurEmission::actif()->orderBy('nom')->get();

        return view('back.environnement.bilans.form', [
            'bilan' => $bilan,
            'facteursProduction' => $facteursProduction,
            'facteursTransport' => $facteursTransport,
            'facteursEmballage' => $facteursEmballage,
            'tousFacteurs' => $tousFacteurs,
        ]);
    }

    public function update(StoreBilanEnvironnementalRequest $request, BilanEnvironnemental $bilan, EcoScoreIAService $service): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('recalculer_ia')) {
            $calculs = $service->analyser($data);
            $data = array_merge($data, $calculs);
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
    public function estimerEcoScoreIA(Request $request, EcoScoreIAService $service): JsonResponse
    {
        $params = $request->all();
        $resultat = $service->analyser($params);

        return response()->json([
            'success' => true,
            'data' => $resultat,
        ]);
    }
}
