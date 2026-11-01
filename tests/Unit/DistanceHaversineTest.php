<?php

namespace Tests\Unit;

use App\Models\Lot;
use PHPUnit\Framework\TestCase;

/**
 * Distance à vol d'oiseau (formule de Haversine), sans base de données.
 */
class DistanceHaversineTest extends TestCase
{
    public function test_deux_points_identiques_sont_a_zero_km(): void
    {
        $this->assertSame(0.0, Lot::distanceKm(36.8065, 10.1815, 36.8065, 10.1815));
    }

    public function test_distance_tunis_sousse(): void
    {
        // Tunis -> Sousse : environ 115 km à vol d'oiseau
        $this->assertEqualsWithDelta(115, Lot::distanceKm(36.8065, 10.1815, 35.8256, 10.6369), 3);
    }

    public function test_distance_paris_lyon_de_reference(): void
    {
        // Valeur de référence connue : environ 392 km
        $this->assertEqualsWithDelta(392, Lot::distanceKm(48.8566, 2.3522, 45.7640, 4.8357), 3);
    }

    public function test_la_distance_est_symetrique(): void
    {
        $this->assertEqualsWithDelta(
            Lot::distanceKm(36.7256, 9.1817, 34.7406, 10.7603),
            Lot::distanceKm(34.7406, 10.7603, 36.7256, 9.1817),
            0.0001
        );
    }
}
