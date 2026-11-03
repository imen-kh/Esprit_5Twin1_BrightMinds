<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Product;
use App\Models\Reclamation;
use App\Services\AvisIAService;
use App\Services\GestionAvisService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace acteur : ce que les clients disent de SES produits (réclamations et avis), droit de réponse.
 */
class RetourClientController extends Controller
{
    use AuthorizesRequests;

    public function reclamations(Request $request): View
    {
        $user = $request->user();

        $reclamations = Reclamation::query()->with(['produit', 'user'])
            ->whereHas('produit', fn ($q) => $q->where('user_id', $user->id))
            ->when(in_array($request->query('statut'), Reclamation::STATUTS, true), fn ($q) => $q->where('statut', $request->query('statut')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $parCategorie = Reclamation::query()
            ->whereHas('produit', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('categorie')
            ->selectRaw('categorie, count(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        return view('acteur.retours.reclamations', compact('reclamations', 'parCategorie'));
    }

    public function avis(Request $request, AvisIAService $ia): View
    {
        $user = $request->user();

        $avis = Avis::query()->with(['produit', 'user'])
            ->publies()
            ->whereHas('produit', fn ($q) => $q->where('user_id', $user->id))
            ->when($request->boolean('sans_reponse'), fn ($q) => $q->whereNull('reponse_producteur'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Points forts / faibles de chaque produit, d'après les aspects notés par l'IA
        $produits = Product::query()->where('user_id', $user->id)
            ->whereHas('avis', fn ($q) => $q->publies())
            ->withAvg(['avis as note_moyenne' => fn ($q) => $q->publies()], 'note')
            ->withCount(['avis as nb_avis' => fn ($q) => $q->publies()])
            ->get()
            ->map(function (Product $produit) use ($ia) {
                $produit->setAttribute('aspects_moyens', $ia->aspectsMoyens($produit->avis()->publies()->get()));

                return $produit;
            });

        return view('acteur.retours.avis', compact('avis', 'produits'));
    }

    public function repondreAvis(Request $request, Avis $avis, GestionAvisService $service): RedirectResponse
    {
        $this->authorize('repondre', $avis);

        $data = $request->validate([
            'reponse_producteur' => ['required', 'string', 'min:5', 'max:1000'],
        ], ['reponse_producteur.required' => 'Écrivez votre réponse.']);

        $service->repondreProducteur($avis, $data['reponse_producteur']);

        return back()->with('success', 'Votre réponse est publiée sous l\'avis.');
    }
}
