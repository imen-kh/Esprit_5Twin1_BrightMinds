<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Services\AiReclamationService;
use Illuminate\Database\Seeder;

class ReclamationSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::query()->where('email', 'client@nutritrace.tn')->first()
            ?? User::factory()->create([
                'name' => 'Client NutriTrace',
                'email' => 'client@nutritrace.tn',
                'role' => 'consommateur',
                'password' => 'password',
            ]);

        $admin = User::query()->where('email', 'admin@nutritrace.tn')->first()
            ?? User::factory()->create([
                'name' => 'Admin NutriTrace',
                'email' => 'admin@nutritrace.tn',
                'role' => 'admin',
                'password' => 'password',
            ]);

        $produits = Product::query()->orderBy('id')->get();
        if ($produits->isEmpty()) {
            $produits = Product::factory()->count(3)->create();
        }

        $exemples = [
            ['sujet' => 'Oranges abîmées à la réception', 'description' => 'Plusieurs oranges étaient pourries dans le filet. Je demande un remboursement.', 'priorite' => 'haute', 'statut' => 'en_cours', 'commande_id' => 1042],
            ['sujet' => 'Retard sur la livraison des dattes', 'description' => 'La commande a plus d\'une semaine de retard et le colis est encore absent.', 'priorite' => 'haute', 'statut' => 'en_attente', 'commande_id' => 1108],
            ['sujet' => 'Harissa trop piquante', 'description' => 'Le produit est excellent mais plus fort que d\'habitude. Simple remarque.', 'priorite' => 'faible', 'statut' => 'resolue', 'commande_id' => null],
            ['sujet' => 'Huile d\'olive au goût parfait', 'description' => 'Merci, je suis très satisfait de cette huile. Produit excellent.', 'priorite' => 'faible', 'statut' => 'resolue', 'commande_id' => 980],
            ['sujet' => 'Tomates manquantes dans le colis', 'description' => 'Il manque un kilo de tomates fraîches. Erreur de préparation.', 'priorite' => 'moyenne', 'statut' => 'en_attente', 'commande_id' => 1215],
            ['sujet' => 'Emballage de citrons cassé', 'description' => 'Le carton était cassé et une partie des citrons a été abîmée.', 'priorite' => 'moyenne', 'statut' => 'en_cours', 'commande_id' => 1330],
            ['sujet' => 'Suspicion d\'intoxication', 'description' => 'Après le repas j\'ai été malade. C\'est urgent, le produit pourrait être toxique.', 'priorite' => 'urgente', 'statut' => 'en_cours', 'commande_id' => 1402],
            ['sujet' => 'Question sur l\'origine des fraises', 'description' => 'Pouvez-vous confirmer que les fraises viennent bien du Cap Bon ?', 'priorite' => 'faible', 'statut' => 'en_attente', 'commande_id' => null],
            ['sujet' => 'Piments trop mous', 'description' => 'Les piments étaient mous et mauvais. Je suis déçu de la qualité.', 'priorite' => 'moyenne', 'statut' => 'rejetee', 'commande_id' => 1511],
            ['sujet' => 'Filet d\'oranges incomplet', 'description' => 'Le poids indiqué ne correspond pas. Quantité manquante.', 'priorite' => 'haute', 'statut' => 'en_attente', 'commande_id' => 1604],
        ];

        $analyse = app(AiReclamationService::class);

        foreach ($exemples as $index => $exemple) {
            $resultat = $analyse->analyserReclamation($exemple['sujet'], $exemple['description']);
            $produit = $produits[$index % $produits->count()];

            $reclamation = Reclamation::query()->updateOrCreate(
                ['user_id' => $client->id, 'sujet' => $exemple['sujet']],
                [
                    'produit_id' => $produit->id,
                    'commande_id' => $exemple['commande_id'],
                    'description' => $exemple['description'],
                    'statut' => $exemple['statut'],
                    'priorite' => $exemple['priorite'],
                    'sentiment' => $resultat['sentiment'],
                    'priorite_suggeree' => $resultat['priorite'],
                    'suggestion_reponse' => $resultat['suggestion'],
                ]
            );

            if ($reclamation->reponses()->exists()) {
                continue;
            }

            if (in_array($exemple['statut'], ['en_cours', 'resolue', 'rejetee'], true)) {
                $reclamation->reponses()->create([
                    'user_id' => $admin->id,
                    'message' => $resultat['suggestion'],
                ]);
            }
        }
    }
}
