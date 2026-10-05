<?php

namespace Database\Factories;

use App\Models\FacteurEmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacteurEmission>
 */
class FacteurEmissionFactory extends Factory
{
    protected $model = FacteurEmission::class;

    public function definition(): array
    {
        $categorie = fake()->randomElement(['transport', 'production', 'emballage', 'energie', 'eau', 'autre']);

        return [
            'nom' => mb_substr(fake()->unique()->words(4, true), 0, 255),
            'code' => strtoupper(fake()->unique()->bothify('FACT_????_####')),
            'categorie' => $categorie,
            'valeur' => fake()->randomFloat(4, 0.0100, 2.8000),
            'unite' => match ($categorie) {
                'transport' => 'kg CO2e / t.km',
                'energie' => 'kg CO2e / kWh',
                'eau' => 'kg CO2e / m3',
                'emballage' => 'kg CO2e / kg emballage',
                default => 'kg CO2e / kg produit',
            },
            'source' => fake()->randomElement(['ADEME', 'ANME Tunisie', 'Agribalyse', 'FAO']),
            'description' => fake()->sentence(12),
            'actif' => true,
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['actif' => false]);
    }
}
