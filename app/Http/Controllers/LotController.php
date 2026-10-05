<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLotRequest;
use App\Http\Requests\UpdateLotRequest;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Gestion des lots (module Traçabilité, back office).
 * Routes protégées par les middlewares « auth » et « acteur ».
 */
class LotController extends Controller
{
    /**
     * Liste des lots visibles par l'utilisateur, avec recherche et filtre par statut.
     */
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('q'));
        $statut = $request->query('statut');

        $lots = Lot::pourUtilisateur($request->user())
            ->with('produit')
            ->withCount('etapes')
            ->when($recherche !== '', function ($query) use ($recherche) {
                // Recherche sur le code du lot ou sur le nom du produit
                $query->where(function ($q) use ($recherche) {
                    $q->where('code', 'like', "%{$recherche}%")
                        ->orWhereHas('produit', fn ($produit) => $produit->where('name', 'like', "%{$recherche}%"));
                });
            })
            ->when(is_string($statut) && isset(Lot::STATUTS[$statut]), fn ($query) => $query->where('statut', $statut))
            ->orderByDesc('date_production')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('back.lots.index', [
            'lots' => $lots,
            'recherche' => $recherche,
            'statut' => $statut,
            'peutCreer' => $this->produitsAccessibles($request->user())->isNotEmpty(),
        ]);
    }

    /**
     * Formulaire de création, avec le prochain code de lot suggéré.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $produits = $this->produitsAccessibles($request->user());

        if ($produits->isEmpty()) {
            return redirect()
                ->route('gestion.lots.index')
                ->with('error', 'Vous devez avoir au moins un produit dans le catalogue pour créer un lot.');
        }

        return view('back.lots.create', [
            'lot' => new Lot(['code' => Lot::prochainCode(), 'statut' => 'en_production', 'unite' => 'kg']),
            'produits' => $produits,
            'lotsOrigine' => $this->lotsOrigine(),
        ]);
    }

    /**
     * Enregistre un nouveau lot (données validées par StoreLotRequest).
     */
    public function store(StoreLotRequest $request): RedirectResponse
    {
        $lot = Lot::create($request->validated());

        return redirect()
            ->route('gestion.lots.show', $lot)
            ->with('success', "Le lot {$lot->code} a été créé avec succès.");
    }

    /**
     * Détail d'un lot : produit, lot d'origine, lots dérivés et étapes.
     */
    public function show(Request $request, Lot $lot): View
    {
        abort_unless($lot->estVisiblePar($request->user()), 403, 'Vous n\'avez pas accès à ce lot.');

        $lot->load(['produit.category', 'produit.user', 'lotOrigine.produit', 'lotsDerives.produit', 'etapes.acteur']);

        return view('back.lots.show', [
            'lot' => $lot,
            'peutModifier' => $lot->estModifiablePar($request->user()),
            'peutAjouterEtape' => $lot->accepteEtapeDe($request->user()),
            // QR code (SVG) vers la page publique de traçabilité du lot
            'qrCode' => (string) QrCode::size(150)->margin(1)->generate(route('tracabilite.show', $lot)),
        ]);
    }

    /**
     * Formulaire de modification, pré-rempli.
     */
    public function edit(Request $request, Lot $lot): View
    {
        abort_unless($lot->estModifiablePar($request->user()), 403, 'Seul le propriétaire du produit peut modifier ce lot.');

        return view('back.lots.edit', [
            'lot' => $lot,
            'produits' => $this->produitsAccessibles($request->user()),
            'lotsOrigine' => $this->lotsOrigine($lot),
        ]);
    }

    /**
     * Met à jour un lot (données validées par UpdateLotRequest).
     */
    public function update(UpdateLotRequest $request, Lot $lot): RedirectResponse
    {
        abort_unless($lot->estModifiablePar($request->user()), 403, 'Seul le propriétaire du produit peut modifier ce lot.');

        $lot->update($request->validated());

        return redirect()
            ->route('gestion.lots.show', $lot)
            ->with('success', "Le lot {$lot->code} a été mis à jour.");
    }

    /**
     * Supprime un lot ; ses étapes sont supprimées en cascade.
     */
    public function destroy(Request $request, Lot $lot): RedirectResponse
    {
        abort_unless($lot->estModifiablePar($request->user()), 403, 'Seul le propriétaire du produit peut supprimer ce lot.');

        $code = $lot->code;
        $lot->delete();

        return redirect()
            ->route('gestion.lots.index')
            ->with('success', "Le lot {$code} et ses étapes ont été supprimés.");
    }

    /**
     * Produits pour lesquels l'utilisateur peut créer un lot : tous pour l'admin, les siens pour un acteur.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Product>
     */
    private function produitsAccessibles(User $user): Collection
    {
        return Product::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Lots proposés comme lot d'origine (matière première). Un transformateur référence le lot
     * d'un autre acteur : tous les lots sont donc proposés, sauf le lot en cours d'édition.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Lot>
     */
    private function lotsOrigine(?Lot $saufLot = null): Collection
    {
        return Lot::with('produit')
            ->when($saufLot, fn ($query) => $query->whereKeyNot($saufLot->id))
            ->orderByDesc('date_production')
            ->get();
    }
}
