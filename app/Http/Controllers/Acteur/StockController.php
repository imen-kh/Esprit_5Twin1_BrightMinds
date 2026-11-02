<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMouvementRequest;
use App\Http\Requests\StockOuvertureRequest;
use App\Http\Requests\StockReglageRequest;
use App\Models\Product;
use App\Models\StockItem;
use App\Services\StockService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private StockService $stocks) {}

    public function index(): View
    {
        $this->authorize('viewAny', StockItem::class);
        $this->stocks->synchroniser(auth()->user());

        $fiches = auth()->user()->stockItems()->with('product')->orderBy('culture_name')->get();

        return view('acteur.stocks.index', [
            'fiches' => $fiches->map(fn (StockItem $fiche) => [
                'fiche' => $fiche,
                'prevision' => $this->stocks->prevoir($fiche, false),
            ]),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockItem::class);

        return view('acteur.stocks.form', [
            'produits' => $this->produits(),
        ]);
    }

    public function store(StockOuvertureRequest $request): RedirectResponse
    {
        $nom = trim($request->string('culture')->toString());
        $produit = $this->produits()->first(
            fn (Product $produit) => mb_strtolower($produit->name) === mb_strtolower($nom)
        );

        $fiche = $this->stocks->ouvrir($request->user(), [
            'culture_name' => $nom,
            'product_id' => $produit?->id,
            'seuil_kg' => $request->float('seuil_kg'),
            'kg_par_unite' => $request->filled('kg_par_unite') ? $request->float('kg_par_unite') : null,
            'quantite_initiale' => $request->filled('quantite_initiale') ? $request->float('quantite_initiale') : null,
        ]);
        $this->stocks->synchroniser($request->user());

        return redirect()
            ->route('acteur.stocks.show', $fiche)
            ->with('success', 'Le stock de '.$fiche->culture_name.' est ouvert.');
    }

    public function show(StockItem $stockItem): View
    {
        $this->authorize('view', $stockItem);
        $this->stocks->synchroniser(auth()->user());
        $stockItem->refresh();

        return view('acteur.stocks.show', [
            'fiche' => $stockItem,
            'mouvements' => $stockItem->movements()->orderByDesc('moved_at')->orderByDesc('id')->get(),
            'prevision' => $this->stocks->prevoir($stockItem),
            'suspens' => $this->stocks->ventesEnSuspens($stockItem),
        ]);
    }

    public function update(StockReglageRequest $request, StockItem $stockItem): RedirectResponse
    {
        $this->authorize('update', $stockItem);
        $stockItem->update([
            'seuil_kg' => $request->float('seuil_kg'),
            'kg_par_unite' => $request->filled('kg_par_unite') ? $request->float('kg_par_unite') : null,
        ]);
        $this->stocks->synchroniser($request->user());

        return redirect()
            ->route('acteur.stocks.show', $stockItem)
            ->with('success', 'Le seuil et le poids par unité sont enregistrés.');
    }

    public function mouvement(StockMouvementRequest $request, StockItem $stockItem): RedirectResponse
    {
        $this->authorize('update', $stockItem);
        $this->stocks->mouvement($stockItem, [
            'type' => $request->string('type')->toString(),
            'quantite_kg' => $request->float('quantite_kg'),
            'motif' => $request->filled('motif') ? $request->string('motif')->toString() : null,
            'moved_at' => $request->date('moved_at')->toDateString(),
        ]);

        return redirect()
            ->route('acteur.stocks.show', $stockItem)
            ->with('success', 'Le mouvement est enregistré.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function produits()
    {
        return Product::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }
}
