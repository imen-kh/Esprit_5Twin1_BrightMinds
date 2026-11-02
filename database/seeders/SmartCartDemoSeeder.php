<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration isolées pour le Smart Cart Optimizer.
 * À exécuter explicitement avec --class=SmartCartDemoSeeder.
 */
class SmartCartDemoSeeder extends Seeder
{
    public function run(): void
    {
        $principal = User::where('email', 'producteur@nutritrace.tn')->firstOrFail();
        $second = User::updateOrCreate(
            ['email' => 'ferme.elbaraka@nutritrace.tn'],
            [
                'name' => 'Ferme El Baraka',
                'role' => 'acteur',
                'type_acteur' => 'producteur',
                'nom_entreprise' => 'Ferme El Baraka',
                'ville' => 'Béja',
                'description' => 'Producteur de démonstration pour comparer les offres du Smart Cart.',
                'password' => 'password',
            ],
        );

        $definitions = [
            ['Tomates fraîches', 'Légumes', 1.600, 1.750, 'Chott Meriem, Sousse'],
            ['Oranges maltaises', 'Fruits', 2.500, 2.300, 'Nabeul, Cap Bon'],
            ['Piments du Cap Bon', 'Légumes', 3.200, 3.450, 'Menzel Bouzelfa'],
            ['Fraises du Cap Bon', 'Fruits', 4.200, 4.600, 'Korba, Cap Bon'],
        ];

        foreach ($definitions as [$name, $categoryName, $principalPrice, $secondPrice, $origin]) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['description' => 'Produits agricoles pour la démonstration du Smart Cart.'],
            );

            $offers = [
                [$principal, $principalPrice, 'SMART-'.strtoupper(substr(md5($name.'principal'), 0, 10))],
                [$second, $secondPrice, 'SMART-'.strtoupper(substr(md5($name.'second'), 0, 10))],
            ];

            foreach ($offers as [$producer, $price, $lotCode]) {
                $product = Product::updateOrCreate(
                    ['name' => $name, 'user_id' => $producer->id],
                    [
                        'category_id' => $category->id,
                        'description' => $name.' disponible pour la démonstration du panier intelligent.',
                        'price' => $price,
                        'origin' => $origin,
                        'ingredients' => null,
                        'status' => 'published',
                    ],
                );

                Lot::updateOrCreate(
                    ['code' => $lotCode],
                    [
                        'produit_id' => $product->id,
                        'date_production' => today()->subDays(5),
                        'date_peremption' => today()->addDays(90),
                        'quantite' => 500,
                        'unite' => 'kg',
                        'statut' => 'disponible',
                        'notes' => 'Lot de démonstration Smart Cart.',
                    ],
                );
            }
        }

        $this->command?->info('Smart Cart : offres et stocks de démonstration créés ou mis à jour.');
    }
}
