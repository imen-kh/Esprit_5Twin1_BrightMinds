<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProduitIAService;
use App\Services\ProduitSimilariteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'user'])->orderByDesc('created_at')->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * File d'attente : seuls les produits soumis, pas encore tranchés, sont listés.
     */
    public function validation(): View
    {
        $products = Product::with(['category', 'user'])
            ->where('status', Product::PENDING)
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.products.validation', compact('products'));
    }

    public function revue(Product $product, ProduitSimilariteService $similarite): View
    {
        $product->load(['category', 'user', 'reviewer']);
        $similaires = $similarite->trouver($product);

        return view('admin.products.revue', compact('product', 'similaires'));
    }

    /**
     * Relit la fiche avec Gemini et enregistre le conseil. Le statut n'est pas modifié.
     */
    public function analyser(Product $product, ProduitIAService $ia, ProduitSimilariteService $similarite): RedirectResponse
    {
        $statut = $product->status;

        if (! $ia->estConfigure()) {
            return back()->with('warning', 'L’IA n’est pas configurée. La décision de publication reste manuelle.');
        }

        $analyse = $ia->analyser($product, $similarite->trouver($product));

        if ($analyse === null) {
            return back()->with('warning', 'L’analyse IA est indisponible. Vous pouvez approuver, refuser ou demander une modification.');
        }

        $product->update([
            'ai_analysis' => $analyse,
            'status' => $statut,
        ]);

        return back()->with('success', 'Analyse enregistrée. Elle ne publie pas le produit : la décision vous appartient.');
    }

    public function approuver(Product $product): RedirectResponse
    {
        $product->update([
            'status' => Product::PUBLISHED,
            'review_note' => null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.produits.validation')
            ->with('success', 'Produit publié dans le catalogue.');
    }

    public function refuser(Request $request, Product $product): RedirectResponse
    {
        $data = $this->motif($request);

        $product->update([
            'status' => Product::REJECTED,
            'review_note' => $data['motif'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.produits.validation')
            ->with('success', 'Produit refusé. Le producteur voit le motif.');
    }

    public function demanderModification(Request $request, Product $product): RedirectResponse
    {
        $data = $this->motif($request);

        $product->update([
            'status' => Product::CHANGES,
            'review_note' => $data['motif'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.produits.validation')
            ->with('success', 'Modification demandée. Le produit n’est pas publié.');
    }

    /**
     * Retire un produit déjà publié. La publication d'un produit en attente passe par approuver().
     */
    public function updateStatus(Product $product): RedirectResponse
    {
        if ($product->status !== Product::PUBLISHED) {
            return redirect()
                ->route('admin.produits.revue', $product)
                ->with('warning', 'Un produit n’est publié qu’après une approbation manuelle.');
        }

        $product->update(['status' => Product::DRAFT]);

        return back()->with('success', 'Produit retiré du catalogue.');
    }

    /**
     * @return array{motif: string}
     */
    private function motif(Request $request): array
    {
        return $request->validate([
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motif.required' => 'Le motif est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères.',
            'motif.max' => 'Le motif ne peut pas dépasser 1000 caractères.',
        ]);
    }
}
