<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

    /**
     * État par défaut d'un lot.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $production = fake()->dateTimeBetween('-3 months', '-1 week');

        return [
            'code' => sprintf('LOT-%s-%s', $production->format('Y'), fake()->unique()->numerify('####')),
            // Relation obligatoire : un produit existant, sinon un produit minimal créé pour l'occasion
            'produit_id' => fn () => Product::query()->inRandomOrder()->value('id') ?? $this->produitMinimal()->id,
            'lot_origine_id' => null,
            'date_production' => $production->format('Y-m-d'),
            'date_peremption' => (clone $production)->modify('+'.fake()->numberBetween(10, 180).' days')->format('Y-m-d'),
            'quantite' => fake()->numberBetween(50, 2000),
            'unite' => fake()->randomElement(Lot::UNITES),
            'statut' => fake()->randomElement(['en_production', 'en_transit', 'disponible']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Lot rappelé (alerte sanitaire).
     */
    public function rappele(): static
    {
        return $this->state(fn () => [
            'statut' => 'rappele',
            'notes' => 'Lot rappelé par précaution à la suite d\'un contrôle qualité non conforme.',
        ]);
    }

    /**
     * Lot transformé issu d'un lot de matière première.
     */
    public function issuDe(Lot $origine): static
    {
        return $this->state(fn () => ['lot_origine_id' => $origine->id]);
    }

    /**
     * Le Catalogue n'a pas de factory : on crée ici le strict nécessaire (catégorie, acteur, produit).
     */
    private function produitMinimal(): Product
    {
        $categorie = Category::firstOrCreate(['name' => 'Fruits'], ['description' => 'Fruits frais de saison.']);

        $producteur = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'producteur',
        ]);

        return Product::create([
            'category_id' => $categorie->id,
            'user_id' => $producteur->id,
            'name' => 'Fraises de Nabeul',
            'description' => 'Fraises cultivées en plein champ au Cap Bon.',
            'price' => 6.500,
            'origin' => 'Nabeul',
            'ingredients' => null,
            'status' => 'published',
        ]);
    }
}
