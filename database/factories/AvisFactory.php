<?php

namespace Database\Factories;

use App\Models\Avis;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avis>
 */
class AvisFactory extends Factory
{
    protected $model = Avis::class;

    public function definition(): array
    {
        $note = fake()->numberBetween(1, 5);

        return [
            'user_id' => User::factory()->state(['role' => 'consommateur']),
            'produit_id' => Product::factory(),
            'note' => $note,
            'titre' => fake()->optional(0.6)->sentence(4),
            'commentaire' => $note >= 4
                ? 'Produit frais et savoureux, livraison rapide. Je recommande.'
                : 'Produit décevant, la fraîcheur n\'était pas au rendez-vous.',
            'statut' => 'publie',
            'achat_verifie' => fake()->boolean(60),
            'sentiment' => $note >= 4 ? 'positif' : ($note <= 2 ? 'negatif' : 'neutre'),
            'aspects' => ['fraicheur' => $note, 'gout' => max(1, $note - 1)],
            'probleme_detecte' => $note <= 2,
        ];
    }
}
