<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BilanEnvironnemental;
use App\Services\EcoScoreIAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnvironnementController extends Controller
{
    public function index(Request $request): View
    {
        $query = BilanEnvironnemental::query()->valide();

        if ($request->filled('eco_score')) {
            $query->where('eco_score', $request->string('eco_score'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('nom_produit', 'like', $q)
                    ->orWhere('origine_geographique', 'like', $q);
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

    public function show(BilanEnvironnemental $bilan): View
    {
        $bilan->load(['facteurProduction', 'facteurTransport', 'facteurEmballage']);

        $autresBilans = BilanEnvironnemental::valide()
            ->where('id', '!=', $bilan->id)
            ->where('eco_score', 'A')
            ->take(3)
            ->get();

        return view('front.environnement-detail', compact('bilan', 'autresBilans'));
    }

    public function simuler(Request $request, EcoScoreIAService $service): JsonResponse
    {
        $resultat = $service->analyser($request->all());

        return response()->json([
            'success' => true,
            'data' => $resultat,
        ]);
    }
}
