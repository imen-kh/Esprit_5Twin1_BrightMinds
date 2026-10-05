<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Livraison;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $distributeurs = User::where('role', 'acteur')->where('type_acteur', 'distributeur')->orderBy('name')->get();
        $livraisons = Livraison::with(['commande', 'distributeur'])
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->string('statut')))
            ->when($request->filled('q'), fn ($query) => $query->where('numero_suivi', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('distributeur_id'), fn ($query) => $query->where('distributeur_id', $request->integer('distributeur_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.livraisons.index', compact('livraisons', 'distributeurs'));
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
