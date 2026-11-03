<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $commandes = Commande::query()
            ->with(['user', 'products', 'livraison'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($subQuery) use ($term) {
                    $subQuery->where('id', is_numeric(trim($term, '%')) ? (int) trim($term, '%') : 0)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('nom', 'like', $term)
                        ->orWhere('prenom', 'like', $term)
                        ->orWhere('commentaire', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term));
                });
            })
            ->latest('date_commande')
            ->paginate(20, ['*'], 'commandes_page')
            ->withQueryString();

        $filtres = function ($query, string $role, ?string $typeActeur = null) use ($request) {
            $query->with(['user', 'products', 'livraison'])
                ->whereHas('user', function ($userQuery) use ($role, $typeActeur) {
                    $userQuery->where('role', $role);
                    if ($typeActeur) {
                        $userQuery->where('type_acteur', $typeActeur);
                    }
                })
                ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
                ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($subQuery) use ($term) {
                    $subQuery->where('id', is_numeric(trim($term, '%')) ? (int) trim($term, '%') : 0)->orWhere('email', 'like', $term)
                        ->orWhere('nom', 'like', $term)->orWhere('prenom', 'like', $term);
                });
                })
                ->latest('date_commande');
            return $query;
        };

        $commandesTransformateurs = $filtres(Commande::query(), 'acteur', 'transformateur')
            ->paginate(15, ['*'], 'transformateur_page')->withQueryString();
        $commandesConsommateurs = $filtres(Commande::query(), 'consommateur')
            ->paginate(15, ['*'], 'consommateur_page')->withQueryString();

        return view('admin.commandes.index', compact('commandes', 'commandesTransformateurs', 'commandesConsommateurs'));
    }

    public function show(Commande $commande)
    {
        $commande->load(['user', 'products', 'livraison.distributeur']);
        return view('admin.commandes.show', compact('commande'));
    }

    public function confirmer(Commande $commande)
    {
        if ($commande->statut !== 'en_attente') {
            return back()->with('error', 'Cette commande ne peut plus être confirmée : elle a déjà été traitée.');
        }

        $commande->update(['statut' => 'confirmee']);

        NotificationService::notifierCommandeConfirmee($commande);

        return back()->with('success', 'Commande confirmée.');
    }

    public function refuser(Commande $commande)
    {
        if ($commande->statut !== 'en_attente') {
            return back()->with('error', 'Cette commande ne peut plus être refusée : elle a déjà été traitée.');
        }

        $commande->update(['statut' => 'refusee']);

        NotificationService::notifierCommandeRefusee($commande);

        return back()->with('success', 'Commande refusée.');
    }

    public function annuler(Commande $commande)
    {
        if (in_array($commande->statut, ['livree', 'refusee', 'annulee'], true)) {
            return back()->with('error', 'Cette commande ne peut plus être annulée.');
        }

        $commande->update(['statut' => 'annulee']);

        NotificationService::notifierCommandeAnnulee($commande);

        return back()->with('success', 'Commande annulée.');
    }
}
