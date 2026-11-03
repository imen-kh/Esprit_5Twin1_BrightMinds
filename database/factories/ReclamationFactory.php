<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Services\AiReclamationService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reclamation>
 */
class ReclamationFactory extends Factory
{
    public function definition(): array
    {
        $sujet = fake()->randomElement([
            'Produit abîmé à la livraison',
            'Retard de commande',
            'Quantité manquante',
            'Excellent produit local',
            'Emballage cassé',
            'Question sur l\'origine',
        ]);
        $description = fake()->paragraph(2);
        // Règles locales : une factory ne doit jamais appeler l'API DeepSeek
        $analyse = app(AiReclamationService::class)->analyseLocale($sujet, $description);

        return [
            'user_id' => User::factory()->state(['role' => 'consommateur']),
            'produit_id' => Product::factory(),
            'commande_id' => fake()->optional(0.4)->numberBetween(1000, 9999),
            'sujet' => $sujet,
            'description' => $description,
            'statut' => fake()->randomElement(Reclamation::STATUTS),
            'priorite' => fake()->randomElement(Reclamation::PRIORITES),
            'sentiment' => $analyse['sentiment'],
            'priorite_suggeree' => $analyse['priorite'],
            'suggestion_reponse' => $analyse['suggestion'],
            'categorie' => $analyse['categorie'],
            'langue' => $analyse['langue'],
            'resume_ia' => $analyse['resume'],
        ];
    }
}
