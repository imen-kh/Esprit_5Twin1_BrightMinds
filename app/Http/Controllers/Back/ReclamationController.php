<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\UpdateReclamationRequest;
use App\Models\Product;
use App\Models\Reclamation;
use App\Services\AiReclamationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reclamation::query()->with(['user', 'produit']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('priorite')) {
            $query->where('priorite', $request->string('priorite'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('sujet', 'like', $q)
                    ->orWhere('description', 'like', $q)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $q))
                    ->orWhereHas('produit', fn ($produit) => $produit->where('name', 'like', $q));
            });
        }

        $reclamations = $query->latest()->paginate(12)->withQueryString();

        return view('back.reclamations.index', compact('reclamations'));
    }

    public function create(): View
    {
        return view('back.reclamations.edit', [
            'reclamation' => new Reclamation(['priorite' => 'moyenne', 'statut' => 'en_attente']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreReclamationRequest $request, AiReclamationService $analyse): RedirectResponse
    {
        $data = $request->validated();
        $resultat = $analyse->analyserReclamation($data['sujet'], $data['description']);

        $reclamation = Reclamation::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'statut' => 'en_attente',
            'sentiment' => $resultat['sentiment'],
            'priorite_suggeree' => $resultat['priorite'],
            'suggestion_reponse' => $resultat['suggestion'],
        ]);

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Réclamation enregistrée.');
    }

    public function show(Reclamation $reclamation): View
    {
        $reclamation->load(['user', 'produit', 'reponses.user']);

        return view('back.reclamations.show', compact('reclamation'));
    }

    public function edit(Reclamation $reclamation): View
    {
        return view('back.reclamations.edit', [
            'reclamation' => $reclamation,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        $reclamation->update($request->validated());

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Réclamation mise à jour.');
    }

    public function updateStatus(Request $request, Reclamation $reclamation): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'string', Rule::in(Reclamation::STATUTS)],
        ]);

        $reclamation->update($data);

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Statut mis à jour.');
    }

    public function destroy(Reclamation $reclamation): RedirectResponse
    {
        $reclamation->delete();

        return redirect()->route('admin.reclamations.index')
            ->with('success', 'Réclamation supprimée.');
    }
}
