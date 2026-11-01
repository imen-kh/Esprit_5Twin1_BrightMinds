<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\HistoriqueLivraison;
use App\Models\Livraison;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Module Commandes et Livraisons : commandes de démonstration couvrant tout le parcours
 * (en attente, confirmée, préparée, en transit, livrée, refusée) avec leurs livraisons.
 * Le consommateur commande des produits du catalogue ; le transformateur commande des
 * PRODUITS BRUTS au producteur (ses matières premières), jamais ses propres produits transformés.
 * Relançable sans doublon : chaque commande est reconnue par son client et son commentaire.
 */
class CommandeLivraisonSeeder extends Seeder
{
    private const FRAIS_LIVRAISON = 3;

    public function run(): void
    {
        $consommateur = User::where('email', 'consommateur@nutritrace.tn')->first();
        $transformateur = User::where('email', 'transformateur@nutritrace.tn')->first();
        $distributeur = User::where('email', 'distributeur@nutritrace.tn')->first();
        $producteur = User::where('email', 'producteur@nutritrace.tn')->first();

        if (! $consommateur || ! $transformateur || ! $distributeur || ! $producteur) {
            $this->command?->warn('CommandeLivraisonSeeder ignoré : comptes de démonstration manquants.');

            return;
        }

        // Catalogue visible par le consommateur, et produits bruts du producteur pour le transformateur
        $catalogue = Product::where('status', 'published')->orderBy('id')->get();
        $bruts = Product::where('user_id', $producteur->id)->where('type_produit', 'matiere_premiere')->orderBy('id')->get();

        if ($catalogue->count() < 2 || $bruts->isEmpty()) {
            $this->command?->warn('CommandeLivraisonSeeder ignoré : produits manquants (lancez d\'abord les seeders du catalogue et de la traçabilité).');

            return;
        }

        // [client, statut de la commande, statut de la livraison (ou null), jours écoulés, [nom du produit => quantité]]
        $scenarios = [
            [$consommateur, 'en_attente', null, 0, ['Oranges maltaises' => 2, 'Confiture de fraises' => 1]],
            [$transformateur, 'en_attente', null, 1, ['Piments de Korba' => 10]],
            [$consommateur, 'confirmee', null, 2, ['Harissa du Cap Bon' => 3, 'Tomates de Béja' => 1]],
            [$transformateur, 'livree', 'livree', 12, ['Fraises de Nabeul' => 20]],
            [$consommateur, 'en_transit', 'en_transit', 5, ['Confiture de fraises' => 2, 'Oranges maltaises' => 1]],
            [$consommateur, 'livree', 'livree', 9, ['Fraises de Nabeul' => 1, 'Harissa du Cap Bon' => 2]],
            [$transformateur, 'refusee', null, 4, ['Tomates de Béja' => 50]],
            [$consommateur, 'preparee', 'en_preparation', 3, ['Tomates de Béja' => 2, 'Confiture de fraises' => 1]],
        ];

        $creees = 0;
        $corrigees = 0;

        foreach ($scenarios as $numero => [$client, $statut, $statutLivraison, $jours, $quantites]) {
            $pourTransformateur = $client->is($transformateur);
            [$lignes, $total] = $this->lignes($quantites, $pourTransformateur ? $bruts : $catalogue);
            $commentaire = 'Commande de démonstration n°'.($numero + 1);

            $existante = Commande::where('user_id', $client->id)->where('commentaire', $commentaire)->first();

            if ($existante) {
                // Commande déjà semée : on remet seulement des produits bruts dans celles du transformateur
                if ($pourTransformateur) {
                    $existante->products()->sync($lignes);
                    $existante->update(['total' => $total + self::FRAIS_LIVRAISON]);
                    $corrigees++;
                }

                continue;
            }

            [$prenom, $nom] = array_pad(explode(' ', $client->name, 2), 2, 'NutriTrace');
            $commande = Commande::create([
                'user_id' => $client->id,
                'distributeur_id' => $statutLivraison ? $distributeur->id : null,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $client->email,
                'telephone' => '22123456',
                'adresse' => $client->adresse ?: '12 rue de la Liberté',
                'ville' => $client->ville ?: 'Tunis',
                'code_postal' => '1000',
                'commentaire' => $commentaire,
                'total' => $total + self::FRAIS_LIVRAISON,
                'statut' => $statut,
                'date_commande' => now()->subDays($jours),
            ]);
            $commande->products()->attach($lignes);
            $creees++;

            if ($statutLivraison) {
                $this->creerLivraison($commande, $distributeur, $statutLivraison);
            }
        }

        $this->command?->info("Commandes et livraisons : {$creees} commande(s) créée(s), {$corrigees} commande(s) du transformateur mise(s) à jour.");
    }

    /**
     * Lignes d'une commande à partir des noms de produits ; un nom introuvable est remplacé
     * par un autre produit de la liste, pour que le seeder fonctionne sur toute base.
     *
     * @param  array<string, int>  $quantites
     * @param  \Illuminate\Support\Collection<int, \App\Models\Product>  $produits
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function lignes(array $quantites, Collection $produits): array
    {
        $lignes = [];
        $total = 0;
        $rang = 0;

        foreach ($quantites as $nom => $quantite) {
            $produit = $produits->firstWhere('name', $nom) ?? $produits[$rang % $produits->count()];
            $rang++;

            if (isset($lignes[$produit->id])) {
                continue;
            }

            $sousTotal = round((float) $produit->price * $quantite, 3);
            $lignes[$produit->id] = ['quantite' => $quantite, 'prix_unitaire' => $produit->price, 'sous_total' => $sousTotal];
            $total += $sousTotal;
        }

        return [$lignes, $total];
    }

    /**
     * Livraison de la commande, avec l'historique de ses changements de statut.
     */
    private function creerLivraison(Commande $commande, User $distributeur, string $statut): void
    {
        $etapes = ['en_preparation', 'expediee', 'en_transit', 'livree'];
        $parcours = array_slice($etapes, 0, array_search($statut, $etapes, true) + 1);
        $debut = $commande->date_commande->copy()->addDay();

        $livraison = Livraison::create([
            'commande_id' => $commande->id,
            'distributeur_id' => $distributeur->id,
            'adresse_livraison' => $commande->adresse,
            'ville' => $commande->ville,
            'code_postal' => $commande->code_postal,
            'telephone' => $commande->telephone,
            'statut' => $statut,
            'numero_suivi' => 'LIV-'.$debut->format('Y').'-D'.str_pad((string) $commande->id, 4, '0', STR_PAD_LEFT),
            'date_expedition' => count($parcours) > 1 ? $debut->copy()->addDay() : null,
            'date_livraison_prevue' => $debut->copy()->addDays(4),
            'date_livraison' => $statut === 'livree' ? $debut->copy()->addDays(3) : null,
            'commentaire' => 'Livraison de démonstration.',
        ]);

        $ancien = null;
        foreach ($parcours as $rang => $nouveau) {
            HistoriqueLivraison::create([
                'livraison_id' => $livraison->id,
                'ancien_statut' => $ancien,
                'nouveau_statut' => $nouveau,
                'user_id' => $distributeur->id,
                'commentaire' => $ancien === null ? 'Livraison créée.' : null,
                'date_changement' => $debut->copy()->addDays($rang),
            ]);
            $ancien = $nouveau;
        }
    }
}
