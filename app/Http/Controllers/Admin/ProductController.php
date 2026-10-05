<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'user'])->orderByDesc('created_at')->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function updateStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 'published' ? 'draft' : 'published',
        ]);

        return back()->with('success', 'Statut du produit modifié.');
    }
}
