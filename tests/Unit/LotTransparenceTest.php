<?php

namespace Tests\Unit;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Règles métier du lot : contrôles automatiques (Lot::anomalies) et score de transparence.
 */
class LotTransparenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lot produit il y a 10 jours, avec ses étapes : [type, ville, heures après la production, température].
     *
     * @param  list<array{0: string, 1: string, 2: int, 3: float|int|null}>  $etapes
     * @param  array<string, mixed>  $attributs
     */
    private function lot(array $etapes, array $attributs = []): Lot
    {
        $production = now()->subDays(10)->startOfDay();

        $lot = Lot::factory()->create([
            'date_production' => $production->toDateString(),
            'date_peremption' => now()->addDays(30)->toDateString(),
            'statut' => 'disponible',
            ...$attributs,
        ]);

        foreach ($etapes as [$type, $ville, $heures, $temperature]) {
            EtapeTracabilite::factory()->type($type)->a($ville)->create([
                'lot_id' => $lot->id,
                'date_etape' => $production->copy()->addHours($heures),
                'temperature' => $temperature,
            ]);
        }

        return $lot->fresh();
    }

    /**
     * Chaîne complète et cohérente, de la ferme au point de vente.
     *
     * @return list<array{0: string, 1: string, 2: int, 3: int}>
     */
    private function chaineParfaite(): array
    {
        return [
            ['production', 'Nabeul', 7, 18],
            ['transport', 'Grombalia', 14, 4],
            ['stockage', 'Tunis', 33, 3],
            ['distribution', 'Tunis', 56, 6],
        ];
    }

    private function types(Lot $lot): array
    {
        return array_column($lot->anomalies(), 'type');
    }

    public function test_un_lot_parfait_n_a_aucune_anomalie_et_un_score_de_100(): void
    {
        $lot = $this->lot($this->chaineParfaite());

        $this->assertSame([], $lot->anomalies());
        $this->assertSame(100, $lot->scoreTransparence());
        $this->assertSame('success', Lot::couleurScore(100));
    }

    public function test_un_trajet_trop_rapide_est_detecte(): void
    {
        // Béja -> Sfax : environ 263 km en 1 heure, bien au-delà de 120 km/h
        $lot = $this->lot([
            ['production', 'Béja', 6, 20],
            ['transport', 'Sfax', 7, 4],
            ['stockage', 'Sfax', 30, 4],
            ['distribution', 'Sfax', 40, 5],
        ]);

        $this->assertContains('vitesse', $this->types($lot));
        $this->assertSame('elevee', collect($lot->anomalies())->firstWhere('type', 'vitesse')['gravite']);
    }

    public function test_un_trajet_realiste_n_est_pas_signale(): void
    {
        // Nabeul -> Grombalia (environ 25 km) en 7 heures
        $this->assertNotContains('vitesse', $this->types($this->lot($this->chaineParfaite())));
    }

    public function test_une_temperature_trop_haute_pendant_le_transport_est_detectee(): void
    {
        $etapes = $this->chaineParfaite();
        $etapes[1][3] = 14;

        $anomalie = collect($this->lot($etapes)->anomalies())->firstWhere('type', 'temperature');

        $this->assertNotNull($anomalie);
        $this->assertStringContainsString('14 °C', $anomalie['message']);
    }

    public function test_le_seuil_de_temperature_est_configurable(): void
    {
        config(['tracabilite.temperature_max' => 15]);

        $etapes = $this->chaineParfaite();
        $etapes[1][3] = 14;

        $this->assertNotContains('temperature', $this->types($this->lot($etapes)));
    }

    public function test_une_temperature_haute_a_la_production_n_est_pas_une_rupture_du_froid(): void
    {
        $etapes = $this->chaineParfaite();
        $etapes[0][3] = 30;

        $this->assertNotContains('temperature', $this->types($this->lot($etapes)));
    }

    public function test_une_distribution_manquante_est_signalee_pour_un_lot_disponible(): void
    {
        $lot = $this->lot(array_slice($this->chaineParfaite(), 0, 3));

        $this->assertContains('etape_manquante', $this->types($lot));
    }

    public function test_un_lot_encore_en_transit_n_a_pas_besoin_de_distribution(): void
    {
        $lot = $this->lot(array_slice($this->chaineParfaite(), 0, 2), ['statut' => 'en_transit']);

        $this->assertNotContains('etape_manquante', $this->types($lot));
    }

    public function test_une_chaine_qui_ne_commence_pas_a_la_production_est_signalee(): void
    {
        $lot = $this->lot(array_slice($this->chaineParfaite(), 1));

        $this->assertContains('etape_manquante', $this->types($lot));
    }

    public function test_le_score_baisse_sans_gps_ni_temperature(): void
    {
        $lot = $this->lot($this->chaineParfaite());
        $lot->etapes()->update(['latitude' => null, 'longitude' => null, 'temperature' => null]);

        // Chaîne complète (40) + produit brut (10) + aucune anomalie (10), sans GPS ni températures
        $this->assertSame(60, $lot->fresh()->scoreTransparence());
        $this->assertSame('warning', Lot::couleurScore(60));
    }

    public function test_le_score_d_un_lot_sans_etape_est_minimal(): void
    {
        $lot = $this->lot([]);

        $this->assertSame([], $lot->anomalies());
        $this->assertSame(10, $lot->scoreTransparence()); // seul le critère « produit brut » est acquis
        $this->assertSame('danger', Lot::couleurScore(10));
    }

    public function test_les_criteres_du_score_totalisent_100_points(): void
    {
        $criteres = $this->lot($this->chaineParfaite())->criteresTransparence();

        $this->assertSame(100, array_sum(array_column($criteres, 'max')));
        $this->assertSame(100, array_sum(array_column($criteres, 'points')));
    }
}
