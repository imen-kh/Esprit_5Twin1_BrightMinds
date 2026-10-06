<?php

namespace Database\Factories;

use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReponseReclamation>
 */
class ReponseReclamationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reclamation_id' => Reclamation::factory(),
            'user_id' => User::factory()->state(['role' => 'admin']),
            'message' => fake()->randomElement([
                'Bonjour, nous avons bien reçu votre réclamation et nous la traitons.',
                'Nous sommes désolés pour ce désagrément. Un remboursement sera proposé.',
                'Merci pour votre retour. L\'équipe qualité a été informée.',
                'Pouvez-vous nous préciser le numéro du lot indiqué sur l\'emballage ?',
            ]),
        ];
    }
}
