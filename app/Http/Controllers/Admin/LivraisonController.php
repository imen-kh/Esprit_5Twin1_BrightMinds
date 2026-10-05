<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Livraison;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $distributeurs = User::where('role', 'acteur')->where('type_acteur', 'distributeur')->orderBy('name')->get();
        $commandesSansLivraison = Commande::with('user')
            ->where('statut', 'confirmee')
            ->whereDoesntHave('livraison')
            ->latest('date_commande')
            ->get();
        $filtres = function ($query, string $role, ?string $typeActeur = null) use ($request) {
            $query->with(['commande.user', 'distributeur'])
                ->whereHas('commande.user', function ($userQuery) use ($role, $typeActeur) {
                    $userQuery->where('role', $role);
                    if ($typeActeur) {
                        $userQuery->where('type_acteur', $typeActeur);
                    }
                })
                ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
                ->when($request->filled('q'), fn ($q) => $q->where('numero_suivi', 'like', '%'.$request->string('q').'%'))
                ->when($request->filled('distributeur_id'), fn ($q) => $q->where('distributeur_id', $request->integer('distributeur_id')))
                ->latest();

            return $query;
        };

        $livraisonsTransformateurs = $filtres(Livraison::query(), 'acteur', 'transformateur')
            ->paginate(10, ['*'], 'transformateur_page')->withQueryString();
        $livraisonsConsommateurs = $filtres(Livraison::query(), 'consommateur')
            ->paginate(10, ['*'], 'consommateur_page')->withQueryString();

        return view('admin.livraisons.index', compact('livraisonsTransformateurs', 'livraisonsConsommateurs', 'commandesSansLivraison', 'distributeurs'));
    }

    public function show(Livraison $livraison)
    {
        $distributeurs = User::where('role', 'acteur')->where('type_acteur', 'distributeur')->orderBy('name')->get();
        $livraison->load(['commande.products', 'commande.user', 'distributeur', 'historique.user']);
        return view('admin.livraisons.show', compact('livraison', 'distributeurs'));
    }

    public function affecter(Request $request, Livraison $livraison)
    {
        abort_unless($livraison->statut !== 'livree', 422, 'Une livraison livrée ne peut plus être réaffectée.');
        $data = $request->validate([
            'distributeur_id' => ['required', 'exists:users,id'],
        ]);
        abort_unless(User::whereKey($data['distributeur_id'])->where('role', 'acteur')->where('type_acteur', 'distributeur')->exists(), 422, 'Le compte choisi n’est pas un distributeur.');

        DB::transaction(function () use ($livraison, $data) {
            $livraison->update(['distributeur_id' => $data['distributeur_id']]);
            $livraison->commande()->update(['distributeur_id' => $data['distributeur_id']]);
        });

        return back()->with('success', 'Distributeur affecté à la livraison.');
    }
}
