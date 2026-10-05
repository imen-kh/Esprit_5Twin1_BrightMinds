<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacteurEmissionRequest;
use App\Models\FacteurEmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacteurEmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = FacteurEmission::query()->withCount('bilans');

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->string('categorie'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', $q)
                    ->orWhere('code', 'like', $q)
                    ->orWhere('source', 'like', $q);
            });
        }

        $facteurs = $query->orderBy('categorie')->orderBy('nom')->paginate(15)->withQueryString();
        $categories = ['transport', 'production', 'emballage', 'energie', 'eau', 'autre'];

        return view('back.environnement.facteurs.index', compact('facteurs', 'categories'));
    }

    public function create(): View
    {
        return view('back.environnement.facteurs.form', [
            'facteur' => new FacteurEmission(['actif' => true, 'categorie' => 'transport']),
            'categories' => ['transport', 'production', 'emballage', 'energie', 'eau', 'autre'],
        ]);
    }

    public function store(StoreFacteurEmissionRequest $request): RedirectResponse
    {
        FacteurEmission::query()->create($request->validated());

        return redirect()->route('admin.facteurs-emission.index')
            ->with('success', 'Facteur d\'émission créé avec succès.');
    }

    public function edit(FacteurEmission $facteurs_emission): View
    {
        return view('back.environnement.facteurs.form', [
            'facteur' => $facteurs_emission,
            'categories' => ['transport', 'production', 'emballage', 'energie', 'eau', 'autre'],
        ]);
    }

    public function update(StoreFacteurEmissionRequest $request, FacteurEmission $facteurs_emission): RedirectResponse
    {
        $facteurs_emission->update($request->validated());

        return redirect()->route('admin.facteurs-emission.index')
            ->with('success', 'Facteur d\'émission mis à jour.');
    }

    public function destroy(FacteurEmission $facteurs_emission): RedirectResponse
    {
        if ($facteurs_emission->bilans()->exists()) {
            return redirect()->route('admin.facteurs-emission.index')
                ->with('error', 'Impossible de supprimer ce facteur car il est rattaché à un ou plusieurs bilans environnementaux.');
        }

        $facteurs_emission->delete();

        return redirect()->route('admin.facteurs-emission.index')
            ->with('success', 'Facteur d\'émission supprimé.');
    }
}
