<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\Panier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommandeSeeder extends Seeder
{
    public function run(): void
    {
        $transformateur = User::where('email', 'transformateur@nutritrace.tn')->first();
        $client = User::where('email', 'client@nutritrace.tn')->first();
        $distributeur = User::where('email', 'distributeur@nutritrace.tn')->first();
        $products = Product::where('status', 'published')->orderBy('id')->take(3)->get();

        if (! $transformateur || ! $client || ! $distributeur || $products->count() < 2) {
            return;
        }

        $this->createDemoCommande($transformateur, $products->take(2), 'DEMO_TRANSFORMATEUR_EN_ATTENTE', 'en_attente');
        $this->createDemoCommande($transformateur, $products->take(2), 'DEMO_TRANSFORMATEUR_CONFIRME', 'confirmee', $distributeur);
        $this->createDemoCommande($client, $products, 'DEMO_CONSOMMATEUR_PREPAREE', 'preparee', $distributeur);
    }

    private function createDemoCommande(User $user, $products, string $marker, string $statut, ?User $distributeur = null): void
    {
        $total = 0;
        $lignes = [];

        foreach ($products as $index => $product) {
            $quantite = $index + 1;
            $sousTotal = round((float) $product->price * $quantite, 3);
            $total += $sousTotal;
            $lignes[$product->id] = [
                'quantite' => $quantite,
                'prix_unitaire' => $product->price,
                'sous_total' => $sousTotal,
            ];
        }

        $commande = Commande::where('user_id', $user->id)->where('commentaire', $marker)->first();
        if (! $commande) {
            $panier = Panier::create(['user_id' => $user->id, 'statut' => 'converti']);
            $commande = Commande::create([
                'panier_id' => $panier->id,
                'distributeur_id' => $distributeur?->id,
                'nom' => $user->name,
                'prenom' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone ?: '22123456',
                'adresse' => $user->adresse ?: 'Adresse de démonstration',
                'ville' => $user->ville ?: 'Tunis',
                'code_postal' => '1000',
                'total' => $total + 3,
                'statut' => $statut,
                'date_commande' => now()->subDays(2),
                'commentaire' => $marker,
            ]);
        }

        $commande->products()->sync($lignes);
    }
}
