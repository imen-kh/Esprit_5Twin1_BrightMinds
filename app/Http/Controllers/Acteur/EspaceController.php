<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Product;

class EspaceController extends Controller
{
    /**
     * L'espace du transformateur est la gestion de ses produits transformés.
     */
    public function transformation()
    {
        return redirect()->route('acteur.produits.index');
    }

    public function distribution()
    {
        $products = Product::with(['category', 'user'])
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate(9);

        return view('acteur.distribution', compact('products'));
    }
}
