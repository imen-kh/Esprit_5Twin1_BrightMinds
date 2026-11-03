<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLotRequest;
use App\Http\Requests\UpdateLotRequest;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use App\Services\MatieresPremieresService;
use App\Services\TracabiliteIAService;
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
        // Filtre par produit : lien « Lots » de la page « Mes produits » du transformateur
        $produit = $this->produitsAccessibles($request->user())->firstWhere('id', (int) $request->query('produit_id'));

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
            ->when($produit, fn ($query) => $query->where('produit_id', $produit->id))
            ->orderByDesc('date_production')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Contrôles automatiques des lots de la page : icône d'alerte selon la gravité la plus haute
        $alertes = $lots->getCollection()->mapWithKeys(function (Lot $lot) {
            $anomalies = $lot->anomalies();

            return [$lot->id => ['nombre' => count($anomalies), 'gravite' => Lot::graviteMax($anomalies)]];
        });

        return view('back.lots.index', [
            'lots' => $lots,
            'alertes' => $alertes,
            'recherche' => $recherche,
            'statut' => $statut,
            'produitFiltre' => $produit,
            'peutCreer' => $this->produitsAccessibles($request->user())->isNotEmpty(),
        ]);
    }

    /**
     * Formulaire de création, avec le prochain code de lot suggéré.
     */
    public function create(Request $request, MatieresPremieresService $matieres): View|RedirectResponse
    {
        $user = $request->user();
        $produits = $this->produitsAccessibles($user);

        if ($produits->isEmpty()) {
            return redirect()
                ->route('gestion.lots.index')
                ->with('error', 'Vous devez avoir au moins un produit dans le catalogue pour créer un lot.');
        }

        // Bouton « Transformer ce lot » : le lot d'origine arrive dans l'URL et reste fixé dans le formulaire
        $origine = null;

        if ($request->filled('lot_origine_id') && Lot::origineAutoriseePour($user)) {
            $origine = Lot::with('produit.user')->find((int) $request->query('lot_origine_id'));
            abort_if(! $origine || ! $matieres->lotAccessible($user, $origine), 403, 'Vous ne pouvez transformer que les lots des matières premières que vous avez commandées.');
        }

        return view('back.lots.create', [
            'lot' => new Lot([
                'code' => Lot::prochainCode(),
                'statut' => 'en_production',
                'unite' => $origine ? 'unités' : 'kg',
                'date_production' => now()->toDateString(),
                'lot_origine_id' => $origine?->id,
            ]),
            'produits' => $produits,
            'lotsOrigine' => $matieres->lotsTransformables($user),
            'origineFixe' => $origine,
        ]);
    }

    /**
     * Enregistre un nouveau lot (données validées par StoreLotRequest).
     */
    public function store(StoreLotRequest $request): RedirectResponse
    {
        $lot = Lot::create($request->validated());

        // Lot transformé créé par un transformateur : on enchaîne sur son étape de transformation
        if ($lot->lot_origine_id && $request->user()->isTransformateur()) {
            return redirect()
                ->route('gestion.etapes.create', ['lot_id' => $lot->id, 'type_etape' => 'transformation'])
                ->with('success', 'Lot créé. Ajoutez maintenant l\'étape de transformation.');
        }

        return redirect()
            ->route('gestion.lots.show', $lot)
            ->with('success', "Le lot {$lot->code} a été créé avec succès.");
    }

    /**
     * Détail d'un lot : produit, lot d'origine, lots dérivés et étapes.
     */
    public function show(Request $request, Lot $lot, TracabiliteIAService $ia): View
    {
        abort_unless($lot->estVisiblePar($request->user()), 403, 'Vous n\'avez pas accès à ce lot.');

        $lot->load(['produit.category', 'produit.user', 'lotOrigine.produit', 'lotsDerives.produit', 'etapes.acteur']);

        // Contrôles automatiques et score de transparence sur la chaîne complète (lots d'origine compris)
        $chaine = $lot->chaineComplete();
        $anomalies = $lot->anomalies($chaine);
        $criteres = $lot->criteresTransparence($chaine, $anomalies);

        return view('back.lots.show', [
            'lot' => $lot,
            'anomalies' => $anomalies,
            'criteres' => $criteres,
            'score' => (int) array_sum(array_column($criteres, 'points')),
            // Histoire et analyse IA : seulement celles déjà en cache, cette page n'attend jamais l'API
            'histoireAJour' => $lot->histoireAJour(),
            'analyseIA' => $ia->analyseEnCache($lot),
            'iaConfiguree' => $ia->estConfigure(),
            'peutModifier' => $lot->estModifiablePar($request->user()),
            'peutAjouterEtape' => $lot->accepteEtapeDe($request->user()),
            // QR code (SVG) vers la page publique de traçabilité du lot
            'qrCode' => (string) QrCode::size(150)->margin(1)->generate(route('tracabilite.show', $lot)),
        ]);
    }

    /**
     * (Re)génère l'histoire IA du produit, affichée sur la page publique.
     */
    public function regenererHistoire(Request $request, Lot $lot, TracabiliteIAService $ia): RedirectResponse
    {
        abort_unless($lot->estVisiblePar($request->user()), 403, 'Vous n\'avez pas accès à ce lot.');

        if (! $ia->estConfigure()) {
            return back()->with('error', 'L\'IA n\'est pas configurée : renseignez ANTHROPIC_API_KEY ou GEMINI_API_KEY dans le fichier .env.');
        }

        return $ia->histoire($lot, forcer: true) !== null
            ? back()->with('success', "L'histoire du lot {$lot->code} a été générée par l'IA.")
            : back()->with('error', 'Le service d\'IA est momentanément indisponible. Réessayez dans quelques instants.');
    }

    /**
     * Analyse IA du lot à partir des anomalies détectées automatiquement.
     */
    public function analyser(Request $request, Lot $lot, TracabiliteIAService $ia): RedirectResponse
    {
        abort_unless($lot->estVisiblePar($request->user()), 403, 'Vous n\'avez pas accès à ce lot.');

        if (! $ia->estConfigure()) {
            return back()->with('error', 'L\'IA n\'est pas configurée : seuls les contrôles automatiques sont affichés.');
        }

        return $ia->analyserEtMemoriser($lot, $lot->anomalies()) !== null
            ? back()->with('success', "Analyse IA du lot {$lot->code} terminée.")
            : back()->with('error', 'Le service d\'IA est momentanément indisponible : seuls les contrôles automatiques sont affichés.');
    }

    /**
     * Formulaire de modification, pré-rempli.
     */
    public function edit(Request $request, Lot $lot, MatieresPremieresService $matieres): View
    {
        abort_unless($lot->estModifiablePar($request->user()), 403, 'Seul le propriétaire du produit peut modifier ce lot.');

        return view('back.lots.edit', [
            'lot' => $lot,
            'produits' => $this->produitsAccessibles($request->user()),
            'lotsOrigine' => $this->lotsOrigine($request->user(), $matieres, $lot),
            'origineFixe' => null,
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
        $lot->loadMissing('produit');
        abort_unless($lot->estModifiablePar($request->user()), 403, 'Seul le propriétaire du produit peut supprimer ce lot.');

        // Supprimer une matière première casserait la chaîne des lots transformés qui en sont issus
        $derives = $lot->lotsDerives()->pluck('code');

        if ($derives->isNotEmpty()) {
            return redirect()
                ->route('gestion.lots.show', $lot)
                ->with('error', "Le lot {$lot->code} ne peut pas être supprimé : il est le lot d'origine de ".$derives->join(', ', ' et ').'.');
        }

        $code = $lot->code;
        $lot->delete();

        return redirect()
            ->route('gestion.lots.index')
            ->with('success', "Le lot {$code} et ses étapes ont été supprimés.");
    }

    /**
     * Produits pour lesquels l'utilisateur peut créer un lot : tous pour l'admin, les siens pour un acteur.
     *
     * @return Collection<int, Product>
     */
    private function produitsAccessibles(User $user): Collection
    {
        return Product::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Lots proposés comme lot d'origine en modification : ceux des matières premières commandées
     * par le transformateur (tous pour l'admin), plus l'origine déjà enregistrée, sauf le lot lui-même.
     *
     * @return Collection<int, Lot>
     */
    private function lotsOrigine(User $user, MatieresPremieresService $matieres, Lot $lot): Collection
    {
        $lots = $matieres->lotsTransformables($user, $lot);

        if ($lot->lotOrigine && ! $lots->contains('id', $lot->lot_origine_id)) {
            $lots->prepend($lot->lotOrigine->load('produit.user'));
        }

        return $lots;
    }
}
