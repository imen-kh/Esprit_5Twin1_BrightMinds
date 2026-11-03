<?php

namespace Tests\Feature\Meteo;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriWeatherTest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.gemini.key' => null]);

        $this->producteur = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'producteur',
            'ville' => 'Nabeul',
            'latitude' => 36.45,
            'longitude' => 10.73,
        ]);
    }

    public function test_le_producteur_voit_la_meteo_et_le_cache_evite_un_second_appel(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->prevision([
                ['date' => '2026-11-03', 'min' => 12, 'max' => 24, 'pluie' => 0, 'vent' => 10],
            ])),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('Nabeul')
            ->assertSee('18.5 °C')
            ->assertSee('60 %')
            ->assertSee('Prévisions sur 7 jours')
            ->assertSee('Aucune alerte');

        $this->actingAs($this->producteur)->get(route('acteur.meteo'))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_les_seuils_de_la_tomate_declenchent_la_chaleur_et_la_pluie(): void
    {
        Product::factory()->create([
            'user_id' => $this->producteur->id,
            'name' => 'Tomate du Sahel',
            'type_produit' => 'matiere_premiere',
        ]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->prevision([
                ['date' => '2026-11-04', 'min' => 16, 'max' => 33, 'pluie' => 28, 'vent' => 55],
            ])),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('Risque de fortes pluies')
            ->assertSee('28 mm prévus le 04/11')
            ->assertSee('Température élevée')
            ->assertSee('Vent violent')
            ->assertSee('Tomate du Sahel')
            ->assertSee('chaleur 32 °C');
    }

    public function test_le_gel_est_signale_sans_inventer_de_pluie(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->prevision([
                ['date' => '2026-11-05', 'min' => 1, 'max' => 14, 'pluie' => 0, 'vent' => 8],
            ])),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('Risque de gel')
            ->assertSee('1 °C attendus le 05/11')
            ->assertDontSee('Risque de fortes pluies');
    }

    public function test_sans_lieu_la_meteo_n_est_pas_appelee(): void
    {
        $this->producteur->update(['ville' => null, 'latitude' => null, 'longitude' => null]);
        Http::fake();

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('Indiquez votre ville ou vos coordonnées GPS');

        Http::assertNothingSent();
    }

    public function test_une_ville_inconnue_affiche_un_message(): void
    {
        $this->producteur->update(['latitude' => null, 'longitude' => null, 'ville' => 'VilleInexistante']);
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => []]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('La météo est indisponible');
    }

    public function test_si_open_meteo_echoue_le_tableau_reste_utilisable(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response('panne', 500),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('La météo est indisponible');
    }

    public function test_gemini_conseille_a_partir_des_chiffres_recus(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->prevision([
                ['date' => '2026-11-04', 'min' => 16, 'max' => 24, 'pluie' => 28, 'vent' => 10],
            ])),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => "Fortes pluies prévues à Nabeul. Vérifiez les conditions avant de récolter vos tomates.\nReportez les travaux du sol si le terrain est détrempé.\nContrôlez le drainage des parcelles basses.",
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo'))
            ->assertOk()
            ->assertSee('Fortes pluies prévues à Nabeul')
            ->assertSee('Généré par IA');

        Http::assertSent(function ($requete) {
            return str_contains($requete->url(), 'generativelanguage.googleapis.com')
                && str_contains($requete->body(), 'pluie 28 mm');
        });
    }

    public function test_une_autre_ville_affiche_ses_previsions_sans_modifier_le_profil(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [[
                    'name' => 'Kasserine',
                    'latitude' => 35.17,
                    'longitude' => 8.84,
                ]],
            ]),
            'api.open-meteo.com/*' => Http::response($this->prevision([
                ['date' => '2026-11-06', 'min' => 8, 'max' => 19, 'pluie' => 0, 'vent' => 14],
            ], 11.2)),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo', ['ville' => 'Kasserine']))
            ->assertOk()
            ->assertSee('Kasserine')
            ->assertSee('11.2 °C')
            ->assertSee('Ma localisation')
            ->assertDontSee('18.5 °C');

        $this->assertDatabaseHas('users', [
            'id' => $this->producteur->id,
            'ville' => 'Nabeul',
            'latitude' => 36.45,
        ]);
    }

    public function test_une_ville_de_recherche_inconnue_reste_saisissable(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => []]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.meteo', ['ville' => 'VilleInexistante']))
            ->assertOk()
            ->assertSee('Aucune localisation trouvée')
            ->assertSee('VilleInexistante');
    }

    public function test_un_transformateur_ne_voit_pas_la_meteo_agricole(): void
    {
        $transformateur = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'transformateur',
        ]);

        $this->actingAs($transformateur)
            ->get(route('acteur.meteo'))
            ->assertForbidden();
    }

    /**
     * @param  list<array{date: string, min: float|int, max: float|int, pluie: float|int, vent: float|int}>  $jours
     * @return array<string, mixed>
     */
    private function prevision(array $jours, float $temperature = 18.5): array
    {
        return [
            'current' => [
                'temperature_2m' => $temperature,
                'relative_humidity_2m' => 60,
                'precipitation' => 0,
                'wind_speed_10m' => 12,
                'weather_code' => 1,
            ],
            'daily' => [
                'time' => array_column($jours, 'date'),
                'temperature_2m_min' => array_column($jours, 'min'),
                'temperature_2m_max' => array_column($jours, 'max'),
                'precipitation_sum' => array_column($jours, 'pluie'),
                'wind_speed_10m_max' => array_column($jours, 'vent'),
                'weather_code' => array_fill(0, count($jours), 1),
            ],
        ];
    }
}
