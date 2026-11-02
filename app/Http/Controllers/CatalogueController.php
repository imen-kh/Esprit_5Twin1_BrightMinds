<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\RechercheIntelligenteService;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function home()
    {
        if ($user = auth()->user()) {
            return redirect()->route($user->espaceRoute());
        }

        $products = Product::with(['category', 'user'])
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return view('front.home', compact('products'));
    }

    public function suggest(Request $request, RechercheIntelligenteService $recherche)
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            $products = Product::with('category')
                ->where('status', Product::PUBLISHED)
                ->orderBy('name')
                ->take(6)
                ->get();
        } else {
            $categories = Category::validated()->orderBy('name')->get();
            $products = $recherche->resultats($recherche->interpreter($term, $categories))->take(6);
        }

        return response()->json($products->map(function (Product $product) {
            return [
                'name' => $product->name,
                'category' => $product->category->name,
                'price' => $product->formattedPrice().' DT',
                'url' => route('produit.detail', $product),
            ];
        }));
    }

    public function index(Request $request, RechercheIntelligenteService $recherche)
    {
        $categories = Category::validated()->orderBy('name')->get();
        $criteres = $recherche->interpreter(trim((string) $request->query('q', '')), $categories);

        if ($request->filled('category_id')) {
            $id = (int) $request->input('category_id');
            $criteres['category_ids'] = $criteres['category_ids'] === []
                ? [$id]
                : array_values(array_intersect($criteres['category_ids'], [$id]));

            if ($criteres['category_ids'] === []) {
                $criteres['category_ids'] = [-1];
            }

            $criteres['resume'] = $recherche->decrire($criteres, $categories);
        }

        $products = $recherche->paginer($criteres);

        return view('front.catalogue.index', compact('categories', 'products', 'criteres'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'published', 404);

        $product->load(['category', 'user']);

        $related = Product::with('category')
            ->where('status', 'published')
            ->whereKeyNot($product->id)
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$product->category_id])
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $bilansPublics = $product->bilansEnvironnementaux()
            ->valide()
            ->with('facteurEmission')
            ->latest()
            ->get();

        return view('front.catalogue.show', compact('product', 'related', 'bilansPublics'));
    }
}
