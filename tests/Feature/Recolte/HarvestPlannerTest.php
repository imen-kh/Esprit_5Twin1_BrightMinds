<?php

namespace Tests\Feature\Recolte;

use App\Models\Harvest;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HarvestPlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    private Product $tomate;

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
        $this->tomate = Product::factory()->create([
            'user_id' => $this->producteur->id,
            'name' => 'Tomate du Sahel',
            'type_produit' => 'matiere_premiere',
        ]);
    }

    public function test_le_producteur_planifie_et_voit_son_calendrier(): void
    {
        $this->actingAs($this->producteur)
            ->post(route('acteur.recoltes.store'), $this->fiche())
            ->assertRedirect();

        $recolte = Harvest::query()->firstOrFail();
        $this->assertNull($recolte->harvested_kg);
        $this->assertSame($this->tomate->id, $recolte->product_id);
        $this->assertSame(Harvest::PLANIFIEE, $recolte->status);

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.index'))
            ->assertOk()
            ->assertSee('Tomate du Sahel')
            ->assertSee('400 kg')
            ->assertSee('calendrier-recoltes');

        $this->actingAs($this->producteur)
            ->getJson(route('acteur.recoltes.evenements'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Tomate du Sahel · 400 kg estimés']);
    }

    public function test_un_producteur_ne_gere_pas_la_recolte_d_un_autre(): void
    {
        $recolte = $this->recolte();
        $autre = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        Product::factory()->create(['user_id' => $autre->id, 'name' => 'Orange maltaise']);

        $this->actingAs($autre)->get(route('acteur.recoltes.show', $recolte))->assertForbidden();
        $this->actingAs($autre)->delete(route('acteur.recoltes.destroy', $recolte))->assertForbidden();

        $this->actingAs($this->producteur)
            ->post(route('acteur.recoltes.store'), $this->fiche(['culture' => 'Pastèque']))
            ->assertRedirect();

        $manuelle = Harvest::query()->where('culture_name', 'Pastèque')->firstOrFail();
        $this->assertNull($manuelle->product_id);

        $this->actingAs($this->producteur)
            ->post(route('acteur.recoltes.store'), $this->fiche(['culture' => 'Orange maltaise']))
            ->assertRedirect();

        $this->assertNull(Harvest::query()->where('culture_name', 'Orange maltaise')->firstOrFail()->product_id);
    }

    public function test_la_quantite_recoltee_est_exigee_et_distincte_de_l_estimee(): void
    {
        $this->fakeMeteo();
        $recolte = $this->recolte();

        $this->actingAs($this->producteur)
            ->put(route('acteur.recoltes.update', $recolte), $this->fiche(['status' => Harvest::RECOLTEE]))
            ->assertSessionHasErrors('harvested_kg');

        $this->actingAs($this->producteur)
            ->put(route('acteur.recoltes.update', $recolte), $this->fiche([
                'status' => Harvest::RECOLTEE,
                'harvested_kg' => 380,
            ]))
            ->assertRedirect(route('acteur.recoltes.show', $recolte));

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.show', $recolte))
            ->assertOk()
            ->assertSee('Quantité estimée déclarée')
            ->assertSee('400 kg')
            ->assertSee('Quantité réellement récoltée')
            ->assertSee('380 kg');
    }

    public function test_la_fenetre_indicative_vient_du_cycle_et_pas_d_une_date_inventee(): void
    {
        $this->fakeMeteo();
        $recolte = $this->recolte([
            'planted_at' => '2026-01-01',
            'planned_harvest_at' => '2026-04-01',
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.show', $recolte))
            ->assertOk()
            ->assertSee('12/03/2026')
            ->assertSee('11/04/2026')
            ->assertSee('Ce n’est pas une date garantie')
            ->assertSee('400 kg');
    }

    public function test_une_recolte_proche_signale_le_risque_meteo_recu(): void
    {
        $jour = Carbon::today()->addDay()->toDateString();
        $recolte = $this->recolte([
            'planted_at' => Carbon::today()->subDays(80)->toDateString(),
            'planned_harvest_at' => $jour,
        ]);
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 18,
                    'relative_humidity_2m' => 70,
                    'precipitation' => 0,
                    'wind_speed_10m' => 10,
                    'weather_code' => 61,
                ],
                'daily' => [
                    'time' => [$jour],
                    'temperature_2m_min' => [14],
                    'temperature_2m_max' => [22],
                    'precipitation_sum' => [30],
                    'wind_speed_10m_max' => [12],
                    'weather_code' => [61],
                ],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.show', $recolte))
            ->assertOk()
            ->assertSee('Risque de fortes pluies')
            ->assertSee('30 mm prévus');
    }

    public function test_sans_localisation_aucun_risque_n_est_invente(): void
    {
        $this->producteur->update(['ville' => null, 'latitude' => null, 'longitude' => null]);
        $recolte = $this->recolte(['planned_harvest_at' => Carbon::today()->addDays(40)->toDateString()]);
        Http::fake();

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.show', $recolte))
            ->assertOk()
            ->assertSee('Indiquez votre ville ou vos coordonnées GPS')
            ->assertDontSee('Risque de fortes pluies');

        Http::assertNothingSent();
    }

    public function test_l_ia_explique_sans_changer_les_chiffres_declares(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);
        $this->producteur->update(['ville' => null, 'latitude' => null, 'longitude' => null]);
        $recolte = $this->recolte();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => 'Vos 400 kg déclarés restent une estimation. La fenêtre indiquée n’est pas une date garantie.',
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.recoltes.show', $recolte))
            ->assertOk()
            ->assertSee('Vos 400 kg déclarés')
            ->assertSee('Générée par IA');

        Http::assertSent(fn ($requete) => str_contains($requete->body(), 'N\'invente aucun rendement')
            && str_contains($requete->body(), '400 kg'));
    }

    public function test_l_admin_voit_les_quantites_par_region_et_l_alerte(): void
    {
        $jour = Carbon::today()->addDay()->toDateString();
        $this->recolte([
            'planted_at' => Carbon::today()->subDays(80)->toDateString(),
            'planned_harvest_at' => $jour,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 18,
                    'relative_humidity_2m' => 60,
                    'precipitation' => 0,
                    'wind_speed_10m' => 10,
                    'weather_code' => 1,
                ],
                'daily' => [
                    'time' => [$jour],
                    'temperature_2m_min' => [12],
                    'temperature_2m_max' => [24],
                    'precipitation_sum' => [0],
                    'wind_speed_10m_max' => [55],
                    'weather_code' => [1],
                ],
            ]),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.recoltes.index', ['region' => 'Nabeul']))
            ->assertOk()
            ->assertSee('Tomate du Sahel')
            ->assertSee('Nabeul')
            ->assertSee('400 kg')
            ->assertSee('Vent violent')
            ->assertSee('graphique-produits');
    }

    public function test_un_transformateur_ne_voit_pas_les_recoltes(): void
    {
        $transformateur = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'transformateur',
        ]);

        $this->actingAs($transformateur)
            ->get(route('acteur.recoltes.index'))
            ->assertForbidden();
    }

    private function fakeMeteo(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 18,
                    'relative_humidity_2m' => 50,
                    'precipitation' => 0,
                    'wind_speed_10m' => 8,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'time' => [Carbon::today()->toDateString()],
                    'temperature_2m_min' => [12],
                    'temperature_2m_max' => [22],
                    'precipitation_sum' => [0],
                    'wind_speed_10m_max' => [10],
                    'weather_code' => [0],
                ],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $remplacement
     * @return array<string, mixed>
     */
    private function fiche(array $remplacement = []): array
    {
        return array_merge([
            'culture' => 'Tomate du Sahel',
            'planted_at' => '2026-01-01',
            'planned_harvest_at' => '2026-04-01',
            'area_hectares' => 1.5,
            'estimated_kg' => 400,
            'status' => Harvest::PLANIFIEE,
        ], $remplacement);
    }

    /**
     * @param  array<string, mixed>  $remplacement
     */
    private function recolte(array $remplacement = []): Harvest
    {
        $fiche = $this->fiche($remplacement);
        $nom = $fiche['culture'];
        unset($fiche['culture']);

        return Harvest::query()->create(array_merge($fiche, [
            'user_id' => $this->producteur->id,
            'product_id' => $this->tomate->id,
            'culture_name' => $nom,
        ]));
    }
}
