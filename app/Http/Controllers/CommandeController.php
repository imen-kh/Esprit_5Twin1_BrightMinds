<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Panier;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    private function clientCommande(): void
    {
        abort_unless(
            in_array(Auth::user()?->role, ['consommateur', 'acteur'], true)
                && (! Auth::user()?->isActeur() || Auth::user()?->isTransformateur()),
            403,
            'Accès réservé aux consommateurs et aux transformateurs.'
        );
    }

    private function panierActif(): Panier
    {
        $panier = Panier::where('user_id', Auth::id())
            ->where('statut', 'actif')
            ->with('products')
            ->first();

        if (! $panier || $panier->products->isEmpty()) {
            redirect()->route('panier')->with('error', 'Votre panier est vide.')->throwResponse();
        }

        return $panier;
    }

    public function create()
    {
        $this->clientCommande();
        $panier = $this->panierActif();

        return view('front.commande', ['panier' => $panier, 'user' => Auth::user()]);
    }

    public function store(Request $request)
    {
        $this->clientCommande();
        $panier = $this->panierActif();

        $data = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}][\p{L} '\-]*$/u"],
            'prenom' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}][\p{L} '\-]*$/u"],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'digits:8', 'regex:/^[234579][0-9]{7}$/'],
            'adresse' => ['required', 'string', 'min:5', 'max:255'],
            'ville' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}][\p{L} '\-]*$/u"],
            'code_postal' => ['required', 'string', 'max:20'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ], [
            '*.required' => 'Ce champ est obligatoire.',
            'telephone.digits' => 'Le téléphone doit contenir exactement 8 chiffres.',
            'telephone.regex' => 'Le téléphone doit être un numéro tunisien valide de 8 chiffres.',
            '*.regex' => 'Le format saisi est invalide.',
        ]);

        $commande = DB::transaction(function () use ($data, $panier) {
            $total = 0;
            foreach ($panier->products as $product) {
                $total += round((float) $product->price * (int) $product->pivot->quantite, 3);
            }
            $total += 3;

            $commande = Commande::create([
                'user_id' => Auth::id(),
                'panier_id' => $panier->id,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'],
                'ville' => $data['ville'],
                'code_postal' => $data['code_postal'],
                'commentaire' => $data['commentaire'] ?? null,
                'total' => $total,
                'statut' => 'en_attente',
                'date_commande' => now(),
            ]);

            foreach ($panier->products as $product) {
                $quantite = (int) $product->pivot->quantite;
                $sousTotal = round((float) $product->price * $quantite, 3);
                $commande->products()->attach($product->id, [
                    'quantite' => $quantite,
                    'prix_unitaire' => $product->price,
                    'sous_total' => $sousTotal,
                ]);
            }

            $panier->update(['statut' => 'converti']);
            return $commande;
        });

        NotificationService::notifierNouvelleCommande($commande);

        return redirect()->route('commandes.show', $commande)
            ->with('success', 'Commande créée avec succès.');
    }

    public function index()
    {
        $this->clientCommande();

        $commandes = Commande::where('user_id', Auth::id())
            ->with(['products', 'livraison'])
            ->latest('date_commande')
            ->paginate(10);

        $vue = Auth::user()->role === 'consommateur'
            ? 'front.commandes.consommateur'
            : 'front.commandes.transformateur';

        return view($vue, compact('commandes'));
    }

    public function show(Commande $commande)
    {
        $this->clientCommande();
        abort_unless($commande->user_id === Auth::id(), 403, 'Cette commande ne vous appartient pas.');

        $commande->load(['products', 'livraison.distributeur', 'livraison.historique']);
        return view('front.commandes.show', compact('commande'));
    }
}
