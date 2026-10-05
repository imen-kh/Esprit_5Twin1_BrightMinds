<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Product;

class EspaceController extends Controller
{
    public function transformation()
    {
        return view('acteur.transformation');
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
