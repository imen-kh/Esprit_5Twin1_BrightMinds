<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certification;
use App\Models\Label;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LabelCertificationSeeder extends Seeder
{
    public function run(): void
    {
        $acteur = User::where('role', 'acteur')->first() ?? User::first();

        if (! $acteur) {
            throw new RuntimeException('Aucun utilisateur disponible pour associer les certifications.');
        }

        $categorie = Category::firstOrCreate(
            ['name' => 'Produits locaux'],
            [
                'description' => 'Produits agricoles locaux certifiés',
                'status' => Category::VALIDATED,
                'origin' => Category::ORIGIN_ADMIN,
                'user_id' => $acteur->id,
            ],
        );

        $produit = Product::firstOrCreate(
            ['name' => "Huile d'olive extra vierge"],
            [
                'category_id' => $categorie->id,
                'user_id' => $acteur->id,
                'description' => 'Huile d’olive tunisienne issue d’une production locale.',
                'price' => '24.900',
                'origin' => 'Tunisie',
                'ingredients' => 'Olives',
                'status' => 'published',
            ],
        );

        $labels = [
            [
                'nom' => 'Bio Tunisie',
                'organisme' => 'Commission Nationale de l’Agriculture Biologique',
                'description' => 'Label garantissant une production biologique conforme aux normes tunisiennes.',
                'logo' => 'labels/bio-tunisie.png',
                'duree_validite_mois' => 12,
                'actif' => true,
            ],
            [
                'nom' => 'Food Quality',
                'organisme' => 'NutriTrace',
                'description' => 'Label de qualité et de traçabilité des produits alimentaires.',
                'logo' => 'labels/food-quality.png',
                'duree_validite_mois' => 24,
                'actif' => true,
            ],
            [
                'nom' => 'Origine Tunisie',
                'organisme' => 'Office du Commerce de Tunisie',
                'description' => 'Label attestant l’origine tunisienne du produit.',
                'logo' => 'labels/origine-tunisie.png',
                'duree_validite_mois' => 12,
                'actif' => true,
            ],
        ];

        foreach ($labels as $data) {
            $label = Label::updateOrCreate(['nom' => $data['nom']], $data);
            $numero = 'CERT-'.strtoupper(substr(md5($label->nom), 0, 8));

            Certification::updateOrCreate(
                ['label_id' => $label->id, 'numero' => $numero],
                [
                    'product_id' => $produit->id,
                    'acteur_id' => $acteur->id,
                    'date_obtention' => now()->subMonth()->toDateString(),
                    'date_expiration' => now()->addMonths($label->duree_validite_mois)->toDateString(),
                    'fichier_pdf' => 'certifications/demo-'.$label->id.'.pdf',
                    'statut' => 'validee',
                    'motif_refus' => null,
                ],
            );
        }
    }
}
