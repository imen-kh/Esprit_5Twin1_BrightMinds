<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function home()
    {
        $products = Product::with(['category', 'user'])
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return view('front.home', compact('products'));
    }

    public function suggest(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $products = Product::with('category')
            ->where('status', 'published')
            ->when($term !== '', function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->take(6)
            ->get();

        return response()->json($products->map(function (Product $product) {
            return [
                'name' => $product->name,
                'category' => $product->category->name,
                'price' => number_format((float) $product->price, 3, ',', ' ').' DT',
                'url' => route('produit.detail', $product),
            ];
        }));
    }

    public function index(Request $request)
    {
        $categories = Category::validated()->orderBy('name')->get();

        $products = Product::with(['category', 'user'])
            ->where('status', 'published')
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->input('q').'%');
            })
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('front.catalogue.index', compact('categories', 'products'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'published', 404);

        $product->load(['category', 'user']);

        return view('front.catalogue.show', compact('product'));
    }
}
