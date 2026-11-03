<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Historique explicitement réservé à la démonstration du Production Planner.
 * Il ne remplace pas les commandes existantes et n'est jamais appelé automatiquement.
 */
class ProductionForecastDemoSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'client@nutritrace.tn')->firstOrFail();
        $distributeur = User::where('type_acteur', 'distributeur')->first();

        $offsets = [16, 14, 12, 10, 8, 6, 4, 2];

        $owners = User::whereIn('type_acteur', ['producteur', 'transformateur'])->get();

        foreach ($owners as $owner) {
            $products = Product::where('user_id', $owner->id)->where('status', 'published')->orderBy('id')->get();

            foreach ($products as $product) {
                $marker = 'DEMO_FORECAST_'.strtoupper($owner->type_acteur).'_PRODUCT_'.$product->id;
                $quantities = array_map(
                    fn (int $index) => 1 + (($product->id + $index) % 6),
                    range(0, count($offsets) - 1),
                );

                foreach ($offsets as $index => $offset) {
                    $quantity = (int) $quantities[$index];
                    $comment = $marker.'_'.$index;
                    $order = Commande::updateOrCreate(
                        ['user_id' => $client->id, 'commentaire' => $comment],
                        [
                            'panier_id' => null,
                            'distributeur_id' => $distributeur?->id,
                            'nom' => $client->name,
                            'prenom' => 'Client',
                            'email' => $client->email,
                            'telephone' => '22123456',
                            'adresse' => 'Adresse de démonstration',
                            'ville' => 'Tunis',
                            'code_postal' => '1000',
                            'total' => round((float) $product->price * $quantity, 3),
                            'statut' => 'livree',
                            'date_commande' => CarbonImmutable::today()->subDays($offset)->setTime(12, 0),
                        ],
                    );

                    $order->products()->sync([
                        $product->id => [
                            'quantite' => $quantity,
                            'prix_unitaire' => $product->price,
                            'sous_total' => round((float) $product->price * $quantity, 3),
                        ],
                    ]);
                }
            }
        }

        $this->command?->info('Historique de démonstration du Production Planner créé ou mis à jour.');
    }
}
