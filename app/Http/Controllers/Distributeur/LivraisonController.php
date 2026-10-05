<?php

namespace App\Http\Controllers\Distributeur;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLivraisonRequest;
use App\Http\Requests\UpdateLivraisonRequest;
use App\Models\Commande;
use App\Models\HistoriqueLivraison;
use App\Models\Livraison;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LivraisonController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'commandes' => Commande::where('statut', 'confirmee')->count(),
            'a_preparer' => Livraison::where('distributeur_id', Auth::id())->where('statut', 'en_preparation')->count(),
            'expediees' => Livraison::where('distributeur_id', Auth::id())->where('statut', 'expediee')->count(),
            'transit' => Livraison::where('distributeur_id', Auth::id())->where('statut', 'en_transit')->count(),
            'livrees' => Livraison::where('distributeur_id', Auth::id())->where('statut', 'livree')->count(),
        ];

        return view('distributeur.dashboard', compact('stats'));
    }

    public function index()
    {
        $commandesSansLivraison = Commande::with('user')
            ->where('statut', 'confirmee')
            ->whereDoesntHave('livraison')
            ->where(function ($query) {
                $query->whereNull('distributeur_id')->orWhere('distributeur_id', Auth::id());
            })
            ->latest('date_commande')
            ->get();

        $livraisons = Livraison::with('commande')
            ->where('distributeur_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('distributeur.livraisons.index', compact('livraisons', 'commandesSansLivraison'));
    }

    public function create(Commande $commande)
    {
        abort_unless($commande->distributeur_id === Auth::id() && $commande->statut === 'preparee' && ! $commande->livraison, 403);
        return view('distributeur.livraisons.create', compact('commande'));
    }

    public function store(StoreLivraisonRequest $request, Commande $commande)
    {
        abort_unless($commande->distributeur_id === Auth::id() && $commande->statut === 'preparee' && ! $commande->livraison, 403);

        // Saisie contrôlée par StoreLivraisonRequest (règles et messages en français)
        $data = $request->validated();

        $livraison = DB::transaction(function () use ($data, $commande) {
            $livraison = Livraison::create($data + [
                'commande_id' => $commande->id,
                'distributeur_id' => Auth::id(),
                'statut' => 'en_preparation',
            ]);
            HistoriqueLivraison::create([
                'livraison_id' => $livraison->id,
                'ancien_statut' => null,
                'nouveau_statut' => 'en_preparation',
                'user_id' => Auth::id(),
                'commentaire' => 'Livraison créée.',
            ]);
            return $livraison;
        });

        return redirect()->route('distributeur.livraisons.show', $livraison)
            ->with('success', 'Livraison créée.');
    }

    public function show(Livraison $livraison)
    {
        abort_unless($livraison->distributeur_id === Auth::id(), 403);
        $livraison->load(['commande.products', 'distributeur', 'historique.user']);
        return view('distributeur.livraisons.show', compact('livraison'));
    }

    public function edit(Livraison $livraison)
    {
        abort_unless($livraison->distributeur_id === Auth::id() && $livraison->statut !== 'livree', 403);
        return view('distributeur.livraisons.edit', compact('livraison'));
    }

    public function update(UpdateLivraisonRequest $request, Livraison $livraison)
    {
        abort_unless($livraison->distributeur_id === Auth::id() && $livraison->statut !== 'livree', 403);

        // Saisie contrôlée par UpdateLivraisonRequest (règles et messages en français)
        $data = $request->validated();
        $livraison->update($data);

        return redirect()->route('distributeur.livraisons.show', $livraison)->with('success', 'Livraison mise à jour.');
    }

    public function updateStatus(Request $request, Livraison $livraison)
    {
        abort_unless($livraison->distributeur_id === Auth::id(), 403);
        $data = $request->validate(['statut' => ['required', 'in:expediee,en_transit,livree,annulee']]);

        $transitions = [
            'en_preparation' => ['expediee', 'annulee'],
            'expediee' => ['en_transit'],
            'en_transit' => ['livree'],
        ];
        abort_unless(in_array($data['statut'], $transitions[$livraison->statut] ?? [], true), 422, 'Transition de livraison invalide.');

        DB::transaction(function () use ($livraison, $data) {
            $status = $data['statut'];
            $ancienStatut = $livraison->statut;
            $livraison->update([
                'statut' => $status,
                'date_expedition' => $status === 'expediee' ? now() : $livraison->date_expedition,
                'date_livraison' => $status === 'livree' ? now() : $livraison->date_livraison,
            ]);
            $commandeStatus = match ($status) {
                'expediee' => 'expediee',
                'en_transit' => 'en_transit',
                'livree' => 'livree',
                'annulee' => 'annulee',
                default => null,
            };
            if ($commandeStatus) {
                $livraison->commande()->update(['statut' => $commandeStatus]);
            }
            HistoriqueLivraison::create([
                'livraison_id' => $livraison->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $status,
                'user_id' => Auth::id(),
                'commentaire' => null,
            ]);
        });

        return back()->with('success', match ($data['statut']) {
            'expediee' => 'Livraison expédiée.',
            'en_transit' => 'Livraison passée en transit.',
            'livree' => 'Livraison marquée comme livrée.',
            default => 'Livraison annulée.',
        });
    }

    public function expedier(Livraison $livraison)
    {
        return $this->changerStatut($livraison, 'expediee');
    }

    public function mettreEnTransit(Livraison $livraison)
    {
        return $this->changerStatut($livraison, 'en_transit');
    }

    public function livrer(Livraison $livraison)
    {
        return $this->changerStatut($livraison, 'livree');
    }

    private function changerStatut(Livraison $livraison, string $statut)
    {
        request()->merge(['statut' => $statut]);
        return $this->updateStatus(request(), $livraison);
    }
}
