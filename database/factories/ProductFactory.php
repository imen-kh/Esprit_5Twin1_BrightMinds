<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => mb_substr(fake()->unique()->words(3, true), 0, 100),
            'description' => fake()->sentence(14),
            'price' => fake()->randomFloat(3, 0.800, 45.000),
            'origin' => fake()->randomElement([
                'Cap Bon, Tunisie',
                'Nabeul, Tunisie',
                'Sousse, Tunisie',
                'Sfax, Tunisie',
                'Béja, Tunisie',
            ]),
            'ingredients' => null,
            'status' => 'published',
            'category_id' => function () {
                return Category::query()->firstOrCreate(
                    ['name' => 'Fruits'],
                    [
                        'description' => 'Fruits frais de saison.',
                        'status' => Category::VALIDATED,
                    ]
                )->id;
            },
            'user_id' => function () {
                return User::query()->where('role', 'acteur')->value('id')
                    ?? User::factory()->create([
                        'role' => 'acteur',
                        'type_acteur' => 'producteur',
                    ])->id;
            },
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
