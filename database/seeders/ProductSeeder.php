<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $producteur = User::query()->where('email', 'producteur@nutritrace.tn')->first()
            ?? User::factory()->create([
                'name' => 'Ferme Ben Salah',
                'email' => 'producteur@nutritrace.tn',
                'role' => 'acteur',
                'type_acteur' => 'producteur',
                'password' => 'password',
            ]);

        $categories = [
            'Fruits' => 'Fruits frais de saison, récoltés en Tunisie.',
            'Légumes' => 'Légumes du marché local.',
            'Épicerie' => 'Produits transformés et conserves.',
        ];

        $categoryIds = [];
        foreach ($categories as $name => $description) {
            $categoryIds[$name] = Category::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'status' => Category::VALIDATED]
            )->id;
        }

        $produits = [
            ['name' => 'Oranges maltaises', 'category' => 'Fruits', 'price' => 2.500, 'origin' => 'Nabeul, Cap Bon', 'description' => 'Oranges maltaises juteuses, récoltées à maturité au Cap Bon.'],
            ['name' => 'Citrons beldi', 'category' => 'Fruits', 'price' => 1.800, 'origin' => 'Hammamet, Tunisie', 'description' => 'Citrons beldi parfumés, cultivés en plein champ.'],
            ['name' => 'Fraises du Cap Bon', 'category' => 'Fruits', 'price' => 4.200, 'origin' => 'Korba, Tunisie', 'description' => 'Fraises de saison, cueillies le matin et livrées le jour même.'],
            ['name' => 'Dattes Deglet Nour', 'category' => 'Fruits', 'price' => 8.900, 'origin' => 'Tozeur, Tunisie', 'description' => 'Dattes Deglet Nour de l\'oasis, souples et naturellement sucrées.'],
            ['name' => 'Tomates fraîches', 'category' => 'Légumes', 'price' => 1.600, 'origin' => 'Chott Meriem, Sousse', 'description' => 'Tomates de plein champ, récoltées à maturité dans le Sahel.'],
            ['name' => 'Piments du Cap Bon', 'category' => 'Légumes', 'price' => 3.200, 'origin' => 'Menzel Bouzelfa, Tunisie', 'description' => 'Piments rouges destinés à la harissa et aux préparations maison.'],
            ['name' => 'Harissa artisanale', 'category' => 'Épicerie', 'price' => 6.500, 'origin' => 'Menzel Bouzelfa, Tunisie', 'description' => 'Harissa préparée avec des piments locaux et de l\'huile d\'olive.', 'ingredients' => 'Piments rouges, ail, coriandre, carvi, huile d\'olive, sel.'],
            ['name' => 'Huile d\'olive Chemlali', 'category' => 'Épicerie', 'price' => 18.000, 'origin' => 'Sfax, Tunisie', 'description' => 'Huile d\'olive extra-vierge issue d\'oliveraies pluviales de Sfax.', 'ingredients' => 'Olives Chemlali.'],
        ];

        foreach ($produits as $produit) {
            Product::query()->updateOrCreate(
                ['name' => $produit['name']],
                Product::factory()->raw([
                    'name' => $produit['name'],
                    'description' => $produit['description'],
                    'price' => $produit['price'],
                    'origin' => $produit['origin'],
                    'ingredients' => $produit['ingredients'] ?? null,
                    'status' => 'published',
                    'category_id' => $categoryIds[$produit['category']],
                    'user_id' => $producteur->id,
                ])
            );
        }
    }
}
