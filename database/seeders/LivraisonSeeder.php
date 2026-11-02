<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\HistoriqueLivraison;
use App\Models\Livraison;
use App\Models\User;
use Illuminate\Database\Seeder;

class LivraisonSeeder extends Seeder
{
    public function run(): void
    {
        $distributeur = User::where('email', 'distributeur@nutritrace.tn')->first();
        if (! $distributeur) {
            return;
        }

        $commandes = Commande::whereIn('commentaire', [
            'DEMO_TRANSFORMATEUR_CONFIRME',
            'DEMO_CONSOMMATEUR_PREPAREE',
        ])->get();

        foreach ($commandes as $commande) {
            if ($commande->livraison) {
                continue;
            }

            $enTransit = $commande->commentaire === 'DEMO_TRANSFORMATEUR_CONFIRME';
            $livraison = Livraison::create([
                'commande_id' => $commande->id,
                'distributeur_id' => $commande->distributeur_id ?: $distributeur->id,
                'adresse_livraison' => $commande->adresse,
                'ville' => $commande->ville,
                'code_postal' => $commande->code_postal,
                'telephone' => $commande->telephone,
                'numero_suivi' => 'TRK-DEMO-'.$commande->id,
                'statut' => $enTransit ? 'en_transit' : 'en_preparation',
                'date_expedition' => $enTransit ? now()->subDay() : null,
                'date_livraison_prevue' => now()->addDays(2)->startOfDay(),
                'commentaire' => 'Livraison de démonstration.',
            ]);

            $this->historique($livraison, null, 'en_preparation', now()->subDays(2), $distributeur);
            if ($enTransit) {
                $this->historique($livraison, 'en_preparation', 'expediee', now()->subDay()->subHours(2), $distributeur);
                $this->historique($livraison, 'expediee', 'en_transit', now()->subDay(), $distributeur);
                $commande->update(['statut' => 'en_transit']);
            }
        }
    }

    private function historique(Livraison $livraison, ?string $ancien, string $nouveau, $date, User $user): void
    {
        HistoriqueLivraison::create([
            'livraison_id' => $livraison->id,
            'ancien_statut' => $ancien,
            'nouveau_statut' => $nouveau,
            'user_id' => $user->id,
            'date_changement' => $date,
            'commentaire' => 'Donnée de démonstration.',
        ]);
    }
}
