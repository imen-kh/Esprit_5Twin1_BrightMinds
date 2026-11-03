<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEtapeRequest;
use App\Http\Requests\UpdateEtapeRequest;
use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Gestion des étapes de traçabilité (module Traçabilité, back office).
 * Routes protégées par les middlewares « auth » et « acteur ».
 */
class EtapeTracabiliteController extends Controller
{
    /**
     * Liste des étapes des lots visibles par l'utilisateur, avec recherche et filtres.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $recherche = trim((string) $request->query('q'));
        $type = $request->query('type_etape');
        $lotId = $request->query('lot_id');

        $etapes = EtapeTracabilite::query()
            ->with(['lot.produit', 'acteur'])
            ->whereHas('lot', fn ($lot) => $lot->pourUtilisateur($user))
            ->when($recherche !== '', function ($query) use ($recherche) {
                // Recherche sur le titre, le lieu, le code du lot ou le nom du produit
                $query->where(function ($q) use ($recherche) {
                    $q->where('titre', 'like', "%{$recherche}%")
                        ->orWhere('lieu', 'like', "%{$recherche}%")
                        ->orWhereHas('lot', fn ($lot) => $lot->where('code', 'like', "%{$recherche}%")
                            ->orWhereHas('produit', fn ($produit) => $produit->where('name', 'like', "%{$recherche}%")));
                });
            })
            ->when(is_string($type) && isset(EtapeTracabilite::TYPES[$type]), fn ($query) => $query->where('type_etape', $type))
            ->when(is_numeric($lotId), fn ($query) => $query->where('lot_id', (int) $lotId))
            ->orderByDesc('date_etape')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('back.etapes.index', [
            'etapes' => $etapes,
            'recherche' => $recherche,
            'type' => $type,
            'lotId' => $lotId,
            'lots' => Lot::pourUtilisateur($user)->with('produit')->orderBy('code')->get(),
            'peutCreer' => EtapeTracabilite::typesPour($user) !== [],
        ]);
    }

    /**
     * Formulaire de création. « ?lot_id=… » pré-sélectionne le lot ; le lieu et les
     * coordonnées GPS sont pré-remplis avec le profil de l'acteur connecté.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $types = EtapeTracabilite::typesPour($user);

        if ($types === []) {
            return redirect()
                ->route('gestion.etapes.index')
                ->with('error', 'Renseignez votre type d\'acteur dans votre profil pour pouvoir déclarer une étape.');
        }

        $lots = $this->lotsAccessibles($user);

        if ($lots->isEmpty()) {
            return redirect()
                ->route('gestion.etapes.index')
                ->with('error', 'Aucun lot n\'est disponible pour y déclarer une étape.');
        }

        $etape = new EtapeTracabilite([
            'lot_id' => $lots->contains('id', (int) $request->query('lot_id')) ? (int) $request->query('lot_id') : null,
            // Type demandé dans l'URL (ex. après la création d'un lot transformé), s'il est autorisé pour l'acteur
            'type_etape' => is_string($request->query('type_etape')) && isset($types[$request->query('type_etape')])
                ? $request->query('type_etape')
                : (count($types) === 1 ? array_key_first($types) : null),
            'lieu' => $user->ville,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'date_etape' => now(),
        ]);

        return view('back.etapes.create', ['etape' => $etape, 'lots' => $lots, 'types' => $types]);
    }

    /**
     * Enregistre une étape : son auteur est toujours l'utilisateur connecté.
     */
    public function store(StoreEtapeRequest $request): RedirectResponse
    {
        $etape = new EtapeTracabilite($request->validated());
        $etape->user_id = $request->user()->id;
        $etape->save();
        $etape->lot->synchroniserStatut();

        return redirect()
            ->route('gestion.lots.show', $etape->lot_id)
            ->with('success', "L'étape « {$etape->titre} » a été ajoutée au lot {$etape->lot->code}.");
    }

    /**
     * Détail d'une étape.
     */
    public function show(Request $request, EtapeTracabilite $etape): View
    {
        abort_unless($etape->estVisiblePar($request->user()), 403, 'Vous n\'avez pas accès à cette étape.');

        $etape->load(['lot.produit', 'acteur']);

        return view('back.etapes.show', [
            'etape' => $etape,
            'peutModifier' => $etape->estModifiablePar($request->user()),
            'lotVisible' => $etape->lot->estVisiblePar($request->user()),
        ]);
    }

    /**
     * Formulaire de modification, pré-rempli.
     */
    public function edit(Request $request, EtapeTracabilite $etape): View
    {
        $user = $request->user();

        abort_unless($etape->estModifiablePar($user), 403, 'Seul l\'auteur de l\'étape peut la modifier.');

        // Le lot et le type actuels restent proposés même s'ils ne font plus partie des choix de l'utilisateur
        $lots = $this->lotsAccessibles($user);

        if (! $lots->contains('id', $etape->lot_id)) {
            $lots->prepend($etape->lot()->with('produit')->first());
        }

        $types = EtapeTracabilite::typesPour($user) + [$etape->type_etape => $etape->type];

        return view('back.etapes.edit', ['etape' => $etape, 'lots' => $lots, 'types' => $types]);
    }

    /**
     * Met à jour une étape (données validées par UpdateEtapeRequest). L'auteur ne change pas.
     */
    public function update(UpdateEtapeRequest $request, EtapeTracabilite $etape): RedirectResponse
    {
        abort_unless($etape->estModifiablePar($request->user()), 403, 'Seul l\'auteur de l\'étape peut la modifier.');

        $ancienLot = $etape->lot;
        $etape->update($request->validated());
        $etape->lot->synchroniserStatut();

        // L'étape a changé de lot : l'ancien lot retrouve le statut de sa dernière étape restante
        if ($ancienLot && $ancienLot->id !== $etape->lot_id) {
            $ancienLot->synchroniserStatut();
        }

        return redirect()
            ->route('gestion.lots.show', $etape->lot_id)
            ->with('success', "L'étape « {$etape->titre} » a été mise à jour.");
    }

    /**
     * Supprime une étape.
     */
    public function destroy(Request $request, EtapeTracabilite $etape): RedirectResponse
    {
        $user = $request->user();

        abort_unless($etape->estModifiablePar($user), 403, 'Seul l\'auteur de l\'étape peut la supprimer.');

        $lot = $etape->lot;
        $titre = $etape->titre;
        $etape->delete();
        $lot?->synchroniserStatut();

        // Retour au lot s'il reste visible (un distributeur peut perdre l'accès en supprimant sa seule étape)
        $retour = $lot && $lot->estVisiblePar($user)
            ? redirect()->route('gestion.lots.show', $lot)
            : redirect()->route('gestion.etapes.index');

        return $retour->with('success', "L'étape « {$titre} » a été supprimée.");
    }

    /**
     * Lots sur lesquels l'utilisateur peut déclarer une étape.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Lot>
     */
    private function lotsAccessibles(User $user): Collection
    {
        return Lot::ouvertAuxEtapes($user)->with('produit')->orderByDesc('date_production')->orderByDesc('id')->get();
    }
}
