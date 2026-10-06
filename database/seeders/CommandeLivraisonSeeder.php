<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\HistoriqueLivraison;
use App\Models\Livraison;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Module Commandes et Livraisons : commandes de démonstration couvrant tout le parcours
 * (en attente, confirmée, préparée, en transit, livrée, refusée) avec leurs livraisons.
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
        $produits = Product::where('status', 'published')->orderBy('id')->take(6)->get();

        if (! $consommateur || ! $transformateur || ! $distributeur || $produits->count() < 2) {
            $this->command?->warn('CommandeLivraisonSeeder ignoré : comptes de démonstration ou produits manquants.');

            return;
        }

        // [client, statut de la commande, statut de la livraison (ou null), jours écoulés, quantités par produit]
        $scenarios = [
            [$consommateur, 'en_attente', null, 0, [0 => 2, 1 => 1]],
            [$transformateur, 'en_attente', null, 1, [2 => 10]],
            [$consommateur, 'confirmee', null, 2, [1 => 3, 3 => 1]],
            [$transformateur, 'preparee', 'en_preparation', 3, [0 => 20, 4 => 5]],
            [$consommateur, 'en_transit', 'en_transit', 5, [2 => 2, 5 => 1]],
            [$consommateur, 'livree', 'livree', 9, [0 => 1, 3 => 2]],
            [$transformateur, 'refusee', null, 4, [1 => 50]],
        ];

        $creees = 0;
        foreach ($scenarios as $numero => [$client, $statut, $statutLivraison, $jours, $quantites]) {
            $commentaire = 'Commande de démonstration n°'.($numero + 1);
            if (Commande::where('user_id', $client->id)->where('commentaire', $commentaire)->exists()) {
                continue;
            }

            $date = now()->subDays($jours);
            $lignes = [];
            $total = 0;
            foreach ($quantites as $index => $quantite) {
                $produit = $produits[$index % $produits->count()];
                if (isset($lignes[$produit->id])) {
                    continue;
                }
                $sousTotal = round((float) $produit->price * $quantite, 3);
                $lignes[$produit->id] = ['quantite' => $quantite, 'prix_unitaire' => $produit->price, 'sous_total' => $sousTotal];
                $total += $sousTotal;
            }

            [$prenom, $nom] = array_pad(explode(' ', $client->name, 2), 2, 'NutriTrace');
            $commande = Commande::create([
                'user_id' => $client->id,
                'distributeur_id' => $statutLivraison ? $distributeur->id : null,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $client->email,
                'telephone' => '22123456',
                'adresse' => '12 rue de la Liberté',
                'ville' => 'Tunis',
                'code_postal' => '1000',
                'commentaire' => $commentaire,
                'total' => $total + self::FRAIS_LIVRAISON,
                'statut' => $statut,
                'date_commande' => $date,
            ]);
            $commande->products()->attach($lignes);
            $creees++;

            if ($statutLivraison) {
                $this->creerLivraison($commande, $distributeur, $statutLivraison);
            }
        }

        $this->command?->info("Commandes et livraisons : {$creees} commande(s) de démonstration créée(s).");
    }

    /**
     * Livraison de la commande, avec l'historique de ses changements de statut.
     */
    private function creerLivraison(Commande $commande, User $distributeur, string $statut): void
    {
        $debut = $commande->date_commande->copy()->addDay();
        $parcours = array_slice(['en_preparation', 'expediee', 'en_transit', 'livree'], 0, array_search($statut, ['en_preparation', 'expediee', 'en_transit', 'livree'], true) + 1);

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
        foreach ($parcours as $etape => $nouveau) {
            HistoriqueLivraison::create([
                'livraison_id' => $livraison->id,
                'ancien_statut' => $ancien,
                'nouveau_statut' => $nouveau,
                'user_id' => $distributeur->id,
                'commentaire' => $ancien === null ? 'Livraison créée.' : null,
                'date_changement' => $debut->copy()->addDays($etape),
            ]);
            $ancien = $nouveau;
        }
    }
}
