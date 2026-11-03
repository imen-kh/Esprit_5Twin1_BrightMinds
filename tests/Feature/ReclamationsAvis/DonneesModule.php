<?php

namespace Tests\Feature\ReclamationsAvis;

use App\Models\Category;
use App\Models\Commande;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Jeu de données commun aux tests du module Réclamations & Avis :
 * un client, un autre client, un administrateur, un producteur et son produit, une commande livrée.
 */
trait DonneesModule
{
    protected User $client;

    protected User $autreClient;

    protected User $admin;

    protected User $producteur;

    protected Product $produit;

    protected Commande $commande;

    protected function preparerModule(): void
    {
        // Rôles Spatie d'abord : chaque compte créé ensuite reçoit son rôle automatiquement
        $this->seed(RolePermissionSeeder::class);

        $this->client = User::factory()->create(['role' => 'consommateur', 'name' => 'Sana Ben Ali']);
        $this->autreClient = User::factory()->create(['role' => 'consommateur']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur', 'name' => 'Ferme du Cap Bon']);

        $categorie = Category::query()->create([
            'name' => 'Fruits '.uniqid(),
            'description' => 'Catégorie de test',
            'status' => Category::VALIDATED,
        ]);

        $this->produit = Product::query()->create([
            'name' => 'Oranges Maltaises',
            'description' => 'Oranges de test',
            'price' => 4.200,
            'status' => Product::PUBLISHED,
            'category_id' => $categorie->id,
            'user_id' => $this->producteur->id,
        ]);

        $this->commande = $this->creerCommande($this->client, $this->produit, 'livree');
    }

    protected function creerCommande(User $user, Product $produit, string $statut = 'livree'): Commande
    {
        $commande = Commande::query()->create([
            'user_id' => $user->id,
            'nom' => 'Test', 'prenom' => 'Client', 'email' => $user->email, 'telephone' => '20000000',
            'adresse' => '1 rue de Carthage', 'ville' => 'Tunis', 'total' => 12.600, 'statut' => $statut,
            'date_commande' => now()->subDays(3),
        ]);
        $commande->products()->attach($produit->id, ['quantite' => 3, 'prix_unitaire' => 4.200, 'sous_total' => 12.600]);

        return $commande;
    }

    protected function creerLot(Product $produit, string $code = 'LOT-2026-0901'): Lot
    {
        return Lot::query()->create([
            'code' => $code,
            'produit_id' => $produit->id,
            'date_production' => now()->subDays(10),
            'quantite' => 100,
            'unite' => 'kg',
            'statut' => 'disponible',
        ]);
    }

    /**
     * @param  array<string, mixed>  $champs
     */
    protected function creerReclamation(User $user, string $sujet, array $champs = []): Reclamation
    {
        return Reclamation::query()->create(array_merge([
            'user_id' => $user->id,
            'produit_id' => $this->produit->id,
            'sujet' => $sujet,
            'description' => 'Description de test suffisamment longue.',
            'statut' => 'en_attente',
            'priorite' => 'moyenne',
        ], $champs));
    }

    /**
     * Réponse simulée de l'API DeepSeek (format chat completions).
     *
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    protected function reponseDeepSeek(array $message): array
    {
        return ['choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => null, ...$message], 'finish_reason' => 'stop']]];
    }
}
