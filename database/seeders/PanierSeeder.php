<?php

namespace Database\Seeders;

use App\Models\Panier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class PanierSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'transformateur@nutritrace.tn',
            'client@nutritrace.tn',
            'consommateur@nutritrace.tn',
        ])->get();
        $products = Product::where('status', 'published')->orderBy('id')->take(3)->get();

        foreach ($users as $user) {
            $panier = Panier::firstOrCreate([
                'user_id' => $user->id,
                'statut' => 'actif',
            ]);

            foreach ($products as $index => $product) {
                $panier->products()->syncWithoutDetaching([
                    $product->id => ['quantite' => $index + 1],
                ]);
            }
        }
    }
}
