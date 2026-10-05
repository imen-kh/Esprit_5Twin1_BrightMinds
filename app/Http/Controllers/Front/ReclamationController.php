<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\UpdateReclamationRequest;
use App\Models\Product;
use App\Models\Reclamation;
use App\Services\AiReclamationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(): View
    {
        $reclamations = Reclamation::query()
            ->with(['produit'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('front.reclamations.index', compact('reclamations'));
    }

    public function create(): View
    {
        return view('front.reclamations.create', [
            'products' => Product::query()->where('status', 'published')->orderBy('name')->get(['id', 'name']),
            'reclamation' => new Reclamation(['priorite' => 'moyenne']),
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

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Réclamation envoyée.');
    }

    public function show(Reclamation $reclamation): View
    {
        $this->autoriserLecture($reclamation);

        $reclamation->load(['user', 'produit', 'reponses.user']);

        return view('front.reclamations.show', compact('reclamation'));
    }

    public function edit(Reclamation $reclamation): View
    {
        $this->autoriserModification($reclamation);

        return view('front.reclamations.edit', [
            'reclamation' => $reclamation,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        $this->autoriserModification($reclamation);

        $reclamation->update($request->safe()->only([
            'produit_id', 'commande_id', 'sujet', 'description', 'priorite',
        ]));

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Réclamation mise à jour.');
    }

    public function destroy(Reclamation $reclamation): RedirectResponse
    {
        $this->autoriserModification($reclamation);
        $reclamation->delete();

        return redirect()->route('reclamations.index')
            ->with('success', 'Réclamation supprimée.');
    }

    private function autoriserLecture(Reclamation $reclamation): void
    {
        abort_unless(
            $reclamation->user_id === auth()->id() || auth()->user()?->isAdmin(),
            403
        );
    }

    private function autoriserModification(Reclamation $reclamation): void
    {
        abort_unless(
            $reclamation->user_id === auth()->id() && $reclamation->statut === 'en_attente',
            403
        );
    }
}
