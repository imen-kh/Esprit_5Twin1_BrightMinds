<?php

namespace Database\Factories;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EtapeTracabilite>
 */
class EtapeTracabiliteFactory extends Factory
{
    protected $model = EtapeTracabilite::class;

    /**
     * Lieux réels en Tunisie avec leurs coordonnées GPS.
     */
    public const LIEUX = [
        'Nabeul' => [36.4561, 10.7376],
        'Grombalia' => [36.6000, 10.5000],
        'Menzel Bouzelfa' => [36.6817, 10.5847],
        'Béja' => [36.7256, 9.1817],
        'Bizerte' => [37.2744, 9.8739],
        'Tunis' => [36.8065, 10.1815],
        'Sousse' => [35.8256, 10.6369],
        'Sfax' => [34.7406, 10.7603],
    ];

    /**
     * Titre type et plage de température réaliste (°C) pour chaque type d'étape.
     */
    private const MODELES = [
        'production' => ['Récolte et tri à la ferme', [12, 26]],
        'transformation' => ['Transformation et conditionnement', [4, 22]],
        'transport' => ['Transport en camion réfrigéré', [2, 8]],
        'stockage' => ['Stockage en chambre froide', [2, 6]],
        'distribution' => ['Mise en rayon au point de vente', [4, 18]],
    ];

    /**
     * État par défaut d'une étape.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(EtapeTracabilite::TYPES));
        $ville = fake()->randomElement(array_keys(self::LIEUX));

        return [
            // Relations obligatoires : un lot et l'acteur qui réalise l'étape
            'lot_id' => Lot::factory(),
            'user_id' => fn () => User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur'])->id,
            'type_etape' => $type,
            'titre' => self::MODELES[$type][0],
            'lieu' => $ville,
            'latitude' => self::LIEUX[$ville][0],
            'longitude' => self::LIEUX[$ville][1],
            'date_etape' => fake()->dateTimeBetween('-1 week', '-1 day'),
            'temperature' => fake()->randomFloat(1, self::MODELES[$type][1][0], self::MODELES[$type][1][1]),
            'description' => fake()->optional(0.5)->sentence(),
        ];
    }

    /**
     * Fixe le type d'étape, avec le titre et la température qui lui correspondent.
     */
    public function type(string $type): static
    {
        return $this->state(fn () => [
            'type_etape' => $type,
            'titre' => self::MODELES[$type][0],
            'temperature' => fake()->randomFloat(1, self::MODELES[$type][1][0], self::MODELES[$type][1][1]),
        ]);
    }

    /**
     * Place l'étape dans une ville connue, avec ses coordonnées GPS.
     */
    public function a(string $ville): static
    {
        return $this->state(fn () => [
            'lieu' => $ville,
            'latitude' => self::LIEUX[$ville][0],
            'longitude' => self::LIEUX[$ville][1],
        ]);
    }
}
