<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Services\DashboardAgricoleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord de l'administrateur : chiffres réels de la plateforme.
 */
class DashboardController extends Controller
{
    public function __construct(private DashboardAgricoleService $agricole) {}

    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date'],
            'produit' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
        ]);
        // Nombre de lots par statut, dans l'ordre des statuts du modèle
        $parStatut = Lot::query()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $lotsParStatut = collect(Lot::STATUTS)->map(fn (array $infos, string $cle) => [
            'label' => $infos['label'],
            'badge' => $infos['badge'],
            'total' => (int) ($parStatut[$cle] ?? 0),
        ]);

        // Nombre d'étapes par type, dans l'ordre de la chaîne
        $parType = EtapeTracabilite::query()->selectRaw('type_etape, count(*) as total')->groupBy('type_etape')->pluck('total', 'type_etape');
        $etapesParType = collect(EtapeTracabilite::TYPES)->map(fn (array $infos, string $cle) => $infos + [
            'total' => (int) ($parType[$cle] ?? 0),
        ]);

        // Nombre d'utilisateurs par rôle, et d'acteurs par type
        $parRole = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $parTypeActeur = User::query()->where('role', 'acteur')->whereNotNull('type_acteur')
            ->selectRaw('type_acteur, count(*) as total')->groupBy('type_acteur')->pluck('total', 'type_acteur');

        return view('back.dashboard', [
            'totaux' => [
                'utilisateurs' => User::count(),
                'produits' => Product::count(),
                'categories' => Category::count(),
                'lots' => Lot::count(),
                'etapes' => EtapeTracabilite::count(),
            ],
            'lotsParStatut' => $lotsParStatut,
            'etapesParType' => $etapesParType,
            'parRole' => $parRole,
            'parTypeActeur' => $parTypeActeur,
            'lotsRappeles' => Lot::with('produit')->where('statut', 'rappele')->orderByDesc('date_production')->get(),
            'dernieresEtapes' => EtapeTracabilite::with(['lot.produit', 'acteur'])->orderByDesc('date_etape')->take(6)->get(),
            // Module Réclamations : nombre par statut et dernières réclamations reçues
            'reclamationsParStatut' => Reclamation::query()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut'),
            'dernieresReclamations' => Reclamation::with(['user', 'produit'])->latest()->take(5)->get(),
            'agricole' => $this->agricole->admin([
                'du' => $filtres['du'] ?? null,
                'au' => $filtres['au'] ?? null,
                'produit' => $filtres['produit'] ?? null,
                'region' => $filtres['region'] ?? null,
            ]),
        ]);
    }
}
