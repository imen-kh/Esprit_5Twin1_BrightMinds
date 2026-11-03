<?php

namespace App\Http\Controllers\Distributeur;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    /**
     * Commandes à traiter : celles des consommateurs et des transformateurs, confirmées par
     * l'admin ou déjà prises en charge par le distributeur connecté. Filtre possible par type de client.
     */
    public function index(Request $request)
    {
        $client = $request->query('client');

        $commandes = Commande::with(['user', 'products', 'livraison'])
            ->where(function ($query) {
                $query->where('distributeur_id', Auth::id())->orWhere('statut', 'confirmee');
            })
            ->whereIn('statut', ['confirmee', 'preparee'])
            ->when($client === 'consommateur', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', 'consommateur')))
            ->when($client === 'transformateur', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('type_acteur', 'transformateur')))
            ->latest('date_commande')
            ->paginate(15)
            ->withQueryString();

        return view('distributeur.commandes.index', compact('commandes', 'client'));
    }

    public function show(Commande $commande)
    {
        abort_unless($commande->statut === 'confirmee' || $commande->distributeur_id === Auth::id(), 403, 'Cette commande n’est pas disponible pour vous.');
        $commande->load(['user', 'products', 'livraison']);

        return view('distributeur.commandes.show', compact('commande'));
    }

    public function prendreEnCharge(Commande $commande)
    {
        // Déjà prise en charge (double clic, ou autre distributeur plus rapide) : message dans la page
        if ($commande->statut !== 'confirmee' || ! is_null($commande->distributeur_id)) {
            return back()->with('error', 'Cette commande est déjà prise en charge.');
        }

        $commande->update(['distributeur_id' => Auth::id(), 'statut' => 'preparee']);

        NotificationService::notifierCommandePreparee($commande);

        return back()->with('success', 'Commande prise en charge avec succès.');
    }

    public function preparer(Commande $commande)
    {
        abort_unless($commande->distributeur_id === Auth::id() && $commande->statut === 'confirmee', 422, 'La commande ne peut pas être préparée.');
        $commande->update(['statut' => 'preparee']);

        return back()->with('success', 'Commande préparée.');
    }
}
