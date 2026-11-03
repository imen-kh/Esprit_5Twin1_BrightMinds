<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvisRequest;
use App\Models\Avis;
use App\Models\AvisReaction;
use App\Models\Product;
use App\Services\Assistant\AssistantOutils;
use App\Services\GestionAvisService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvisController extends Controller
{
    use AuthorizesRequests;

    /**
     * « Mes avis » : avis du client et produits reçus qui attendent encore une note.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('front.avis.index', [
            'avis' => $user->avis()->with('produit')->latest()->paginate(10),
            'aNoter' => app(AssistantOutils::class)->produitsNonEvalues($user),
        ]);
    }

    public function store(StoreAvisRequest $request, Product $product, GestionAvisService $service): RedirectResponse
    {
        $this->authorize('create', [Avis::class, $product]);

        $avis = $service->publier($request->user(), $product, $request->validated());

        $message = $avis->statut === 'publie'
            ? 'Merci ! Votre avis est publié.'
            : 'Merci ! Votre avis sera publié après relecture ('.mb_strtolower((string) $avis->moderation_raison).').';

        return redirect()->to(route('produit.detail', $product).'#avis')
            ->with('success', $message)
            ->with('avis_a_convertir', $avis->meriteReclamation() ? $avis->id : null);
    }

    public function edit(Avis $avis): View
    {
        $this->authorize('update', $avis);

        return view('front.avis.edit', ['avis' => $avis->load('produit')]);
    }

    public function update(StoreAvisRequest $request, Avis $avis, GestionAvisService $service): RedirectResponse
    {
        $this->authorize('update', $avis);

        $service->modifier($avis, $request->validated());

        return redirect()->to(route('produit.detail', $avis->produit_id).'#avis')->with('success', 'Votre avis est mis à jour.');
    }

    public function destroy(Avis $avis): RedirectResponse
    {
        $this->authorize('delete', $avis);
        $produit = $avis->produit_id;
        $avis->delete();

        return redirect()->to(route('produit.detail', $produit).'#avis')->with('success', 'Avis supprimé.');
    }

    /**
     * Vote « utile » (bascule) ou signalement.
     */
    public function reagir(Request $request, Avis $avis, GestionAvisService $service): RedirectResponse
    {
        $this->authorize('reagir', $avis);

        $data = $request->validate([
            'type' => ['required', Rule::in(['utile', 'signalement'])],
            'motif' => ['nullable', 'required_if:type,signalement', Rule::in(array_keys(AvisReaction::MOTIFS_SIGNALEMENT))],
        ]);

        $service->reagir($avis, $request->user(), $data['type'], $data['motif'] ?? null);

        return redirect()->to(route('produit.detail', $avis->produit_id).'#avis-'.$avis->id)
            ->with('success', $data['type'] === 'utile' ? 'Merci pour votre vote.' : 'Merci, l\'avis a été signalé à l\'équipe.');
    }

    /**
     * Avis négatif → réclamation pré-remplie (un clic).
     */
    public function convertir(Request $request, Avis $avis, GestionAvisService $service): RedirectResponse
    {
        $this->authorize('convertir', $avis);

        $reclamation = $service->convertirEnReclamation($avis, $request->user());

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Réclamation '.$reclamation->reference.' ouverte à partir de votre avis. Nous revenons vers vous rapidement.');
    }
}
