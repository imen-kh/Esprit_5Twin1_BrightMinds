<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\UpdateReclamationRequest;
use App\Models\Commande;
use App\Models\Product;
use App\Models\Reclamation;
use App\Services\GestionReclamationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $user = $request->user();
        $base = Reclamation::query()->where('user_id', $user->id);

        $reclamations = (clone $base)
            ->with(['produit'])
            ->when(in_array($request->query('statut'), Reclamation::STATUTS, true), fn ($q) => $q->where('statut', $request->query('statut')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $compteurs = [
            'total' => (clone $base)->count(),
            'ouvertes' => (clone $base)->ouvertes()->count(),
            'resolues' => (clone $base)->where('statut', 'resolue')->count(),
            'a_evaluer' => (clone $base)->whereIn('statut', Reclamation::STATUTS_CLOTURES)->whereNull('satisfaction_note')->count(),
        ];

        $notifications = $user->unreadNotifications()->take(5)->get();

        return view('front.reclamations.index', compact('reclamations', 'compteurs', 'notifications'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Reclamation::class);

        $reclamation = new Reclamation([
            'priorite' => 'moyenne',
            'produit_id' => $request->integer('produit') ?: null,
            'commande_id' => $request->integer('commande') ?: null,
        ]);

        return view('front.reclamations.create', [
            'products' => Product::query()->where('status', Product::PUBLISHED)->orderBy('name')->get(['id', 'name']),
            'commandes' => $this->commandesDuClient($request),
            'reclamation' => $reclamation,
        ]);
    }

    public function store(StoreReclamationRequest $request, GestionReclamationService $service): RedirectResponse
    {
        $this->authorize('create', Reclamation::class);

        $reclamation = $service->creer($request->user(), $request->donnees());

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Réclamation '.$reclamation->reference.' envoyée. Délai de traitement : '.mb_strtolower($reclamation->slaLibelle()).'.');
    }

    public function show(Request $request, Reclamation $reclamation): View
    {
        $this->authorize('view', $reclamation);

        $reclamation->load(['user', 'produit', 'lot', 'commande', 'reponses.user', 'historique.user']);

        // Les notifications de ce dossier sont lues en l'ouvrant
        $request->user()->unreadNotifications()
            ->where('data->reference', $reclamation->reference)
            ->get()
            ->markAsRead();

        return view('front.reclamations.show', compact('reclamation'));
    }

    public function edit(Request $request, Reclamation $reclamation): View
    {
        $this->authorize('update', $reclamation);

        return view('front.reclamations.edit', [
            'reclamation' => $reclamation,
            'products' => Product::query()->where('status', Product::PUBLISHED)->orWhere('id', $reclamation->produit_id)->orderBy('name')->get(['id', 'name']),
            'commandes' => $this->commandesDuClient($request),
        ]);
    }

    public function update(UpdateReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        $this->authorize('update', $reclamation);

        $donnees = collect($request->donnees())->only(['produit_id', 'commande_id', 'lot_id', 'sujet', 'description', 'priorite'])->all();
        $reclamation->update($donnees);
        $reclamation->journaliser('statut', 'Réclamation modifiée par le client', $request->user());

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Réclamation mise à jour.');
    }

    public function destroy(Reclamation $reclamation): RedirectResponse
    {
        $this->authorize('delete', $reclamation);
        $reclamation->delete();

        return redirect()->route('reclamations.index')
            ->with('success', 'Réclamation supprimée.');
    }

    /**
     * Le client indique que son problème est réglé.
     */
    public function cloturer(Request $request, Reclamation $reclamation, GestionReclamationService $service): RedirectResponse
    {
        $this->authorize('cloturer', $reclamation);

        $service->changerStatut($reclamation, 'resolue', $request->user());

        return redirect()->route('reclamations.show', $reclamation)
            ->with('success', 'Merci ! Votre réclamation est clôturée. Dites-nous comment s\'est passé le traitement.');
    }

    /**
     * Enquête de satisfaction après clôture.
     */
    public function evaluer(Request $request, Reclamation $reclamation, GestionReclamationService $service): RedirectResponse
    {
        $this->authorize('evaluer', $reclamation);

        $data = $request->validate([
            'satisfaction_note' => ['required', 'integer', 'between:1,5'],
            'satisfaction_commentaire' => ['nullable', 'string', 'max:1000'],
        ], ['satisfaction_note.required' => 'Choisissez une note de 1 à 5.']);

        $service->evaluer($reclamation, $request->user(), (int) $data['satisfaction_note'], $data['satisfaction_commentaire'] ?? null);

        return redirect()->route('reclamations.show', $reclamation)->with('success', 'Merci pour votre évaluation !');
    }

    /**
     * Dossier en retard sur son délai : le client relance, la priorité monte d'un cran.
     */
    public function relancer(Request $request, Reclamation $reclamation, GestionReclamationService $service): RedirectResponse
    {
        abort_unless($reclamation->user_id === $request->user()->id && $reclamation->estEnRetard(), 403);

        $service->escalader($reclamation, $request->user(), 'Relance du client : délai de traitement dépassé');

        return redirect()->route('reclamations.show', $reclamation)->with('success', 'Relance envoyée : votre dossier passe en priorité '.$reclamation->fresh()->prioriteLibelle().'.');
    }

    /**
     * Commandes du client, avec leurs produits, pour lier la réclamation à un achat réel.
     */
    private function commandesDuClient(Request $request)
    {
        return Commande::query()->with('products:id,name')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->take(20)
            ->get(['id', 'statut', 'total', 'created_at', 'date_commande']);
    }
}
