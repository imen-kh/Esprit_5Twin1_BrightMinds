<?php

namespace App\Http\Controllers\Distributeur;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    public function index()
    {
        $commandes = Commande::with(['user', 'products', 'livraison'])
            ->where(function ($query) {
                $query->where('distributeur_id', Auth::id())->orWhere('statut', 'confirmee');
            })
            ->whereIn('statut', ['confirmee', 'preparee'])
            ->latest('date_commande')
            ->paginate(15);

        return view('distributeur.commandes.index', compact('commandes'));
    }

    /**
     * Commandes passées par les consommateurs : celles qui attendent un distributeur
     * (confirmées par l'admin) et celles que le distributeur connecté a prises en charge.
     */
    public function consommateurs(Request $request)
    {
        $statut = $request->query('statut');

        $commandes = Commande::with(['user', 'products', 'livraison'])
            ->whereHas('user', fn ($query) => $query->where('role', 'consommateur'))
            ->where(function ($query) {
                $query->where('statut', 'confirmee')->orWhere('distributeur_id', Auth::id());
            })
            ->when(in_array($statut, Commande::STATUTS, true), fn ($query) => $query->where('statut', $statut))
            ->latest('date_commande')
            ->paginate(10)
            ->withQueryString();

        return view('distributeur.commandes.consommateurs', compact('commandes', 'statut'));
    }

    public function show(Commande $commande)
    {
        abort_unless($commande->statut === 'confirmee' || $commande->distributeur_id === Auth::id(), 403, 'Cette commande n’est pas disponible pour vous.');
        $commande->load(['user', 'products', 'livraison']);

        return view('distributeur.commandes.show', compact('commande'));
    }

    public function prendreEnCharge(Commande $commande)
    {
        abort_unless($commande->statut === 'confirmee' && is_null($commande->distributeur_id), 422, 'Cette commande est déjà prise en charge.');
        $commande->update(['distributeur_id' => Auth::id(), 'statut' => 'preparee']);

        return back()->with('success', 'Commande prise en charge avec succès.');
    }

    public function preparer(Commande $commande)
    {
        abort_unless($commande->distributeur_id === Auth::id() && $commande->statut === 'confirmee', 422, 'La commande ne peut pas être préparée.');
        $commande->update(['statut' => 'preparee']);

        return back()->with('success', 'Commande préparée.');
    }
}
