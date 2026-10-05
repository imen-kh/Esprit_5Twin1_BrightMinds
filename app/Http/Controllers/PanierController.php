<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Panier;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class PanierController extends Controller
{
    private function getCart()
    {
        if (Auth::check()) {
            $panier = Panier::firstOrCreate(
                ['user_id' => Auth::id(), 'statut' => 'actif']
            );
            // Link session cart to user if it exists and user cart is empty
            $session_panier_id = session('panier_id');
            if ($session_panier_id && $session_panier_id != $panier->id) {
                $sessionPanier = Panier::find($session_panier_id);
                if ($sessionPanier && $sessionPanier->statut === 'actif') {
                    foreach ($sessionPanier->products as $product) {
                        $existingProduct = $panier->products()->where('product_id', $product->id)->first();
                        if ($existingProduct) {
                            $panier->products()->updateExistingPivot($product->id, [
                                'quantite' => $existingProduct->pivot->quantite + $product->pivot->quantite
                            ]);
                        } else {
                            $panier->products()->attach($product->id, ['quantite' => $product->pivot->quantite]);
                        }
                    }
                    $sessionPanier->delete();
                    session()->forget('panier_id');
                }
            }
            return $panier;
        } else {
            $panier_id = session('panier_id');
            if ($panier_id) {
                $panier = Panier::where('id', $panier_id)->where('statut', 'actif')->first();
                if ($panier) {
                    return $panier;
                }
            }
            $panier = Panier::create(['statut' => 'actif']);
            session(['panier_id' => $panier->id]);
            return $panier;
        }
    }

    public function index()
    {
        $panier = $this->getCart();
        $panier->load('products');
        return view('front.panier', compact('panier'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantite' => 'required|integer|min:1'
        ]);

        $panier = $this->getCart();
        $product_id = $request->product_id;
        $quantite = $request->quantite;

        $existingProduct = $panier->products()->where('product_id', $product_id)->first();

        if ($existingProduct) {
            $panier->products()->updateExistingPivot($product_id, [
                'quantite' => $existingProduct->pivot->quantite + $quantite
            ]);
        } else {
            $panier->products()->attach($product_id, ['quantite' => $quantite]);
        }

        return redirect()->back()->with('success', 'Produit ajouté au panier.');
    }

    public function update(Request $request, $product_id)
    {
        $request->validate([
            'quantite' => 'required|integer|min:1'
        ]);

        $panier = $this->getCart();
        
        $panier->products()->updateExistingPivot($product_id, [
            'quantite' => $request->quantite
        ]);

        return redirect()->route('panier')->with('success', 'Quantité modifiée.');
    }

    public function remove($product_id)
    {
        $panier = $this->getCart();
        $panier->products()->detach($product_id);

        return redirect()->route('panier')->with('success', 'Produit retiré du panier.');
    }
}
