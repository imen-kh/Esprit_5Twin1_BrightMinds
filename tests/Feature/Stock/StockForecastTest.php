<?php

namespace Tests\Feature\Stock;

use App\Models\Commande;
use App\Models\Harvest;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StockForecastTest extends TestCase
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

    public function test_une_recolte_est_versee_une_seule_fois_et_le_lot_est_ignore(): void
    {
        $this->recolte([
            'status' => Harvest::RECOLTEE,
            'harvested_kg' => 100,
            'planned_harvest_at' => Carbon::today()->subDay()->toDateString(),
        ]);
        Lot::factory()->create([
            'produit_id' => $this->tomate->id,
            'quantite' => 999,
            'unite' => 'kg',
        ]);

        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.store'), [
                'culture' => 'Tomate du Sahel',
                'seuil_kg' => 20,
            ])
            ->assertRedirect();

        $fiche = StockItem::query()->firstOrFail();
        $this->assertSame($this->tomate->id, $fiche->product_id);

        $this->actingAs($this->producteur)->get(route('acteur.stocks.index'))->assertOk()->assertSee('100 kg');
        $this->actingAs($this->producteur)->get(route('acteur.stocks.index'))->assertOk()->assertSee('100 kg')->assertDontSee('1099');

        $this->assertSame(1, StockMovement::query()->where('harvest_id', Harvest::query()->value('id'))->count());
        $this->assertSame(100.0, $fiche->refresh()->disponible());
    }

    public function test_seules_les_ventes_confirmees_sont_deduites_sans_stock_negatif(): void
    {
        $fiche = $this->ouvrir(['quantite_initiale' => 50, 'kg_par_unite' => 2, 'seuil_kg' => 10]);
        $commande = $this->commande('confirmee', 3);

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('44 kg')
            ->assertSee('Commande confirmée');

        $ligneId = DB::table('commande_produit')->where('commande_id', $commande->id)->value('id');
        $this->actingAs($this->producteur)->get(route('acteur.stocks.show', $fiche))->assertOk();
        $this->assertSame(1, StockMovement::query()->where('commande_produit_id', $ligneId)->count());
        $this->assertSame(44.0, $fiche->refresh()->disponible());

        $commande->update(['statut' => 'annulee']);
        $this->commande('en_attente', 20);
        $this->commande('refusee', 8);

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('50 kg')
            ->assertDontSee('44 kg');

        $this->assertSame(50.0, $fiche->refresh()->disponible());
        $this->assertSame(0, StockMovement::query()->where('source', StockMovement::COMMANDE)->count());

        $this->actingAs($this->producteur)
            ->patch(route('acteur.stocks.update', $fiche), ['seuil_kg' => 10])
            ->assertRedirect();
        $this->commande('confirmee', 4);

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('ne sont pas déduites');
        $this->assertSame(50.0, $fiche->refresh()->disponible());

        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.mouvements.store', $fiche), [
                'type' => 'sortie',
                'quantite_kg' => 80,
                'moved_at' => Carbon::today()->toDateString(),
                'motif' => 'Vente trop grande',
            ])
            ->assertSessionHasErrors('quantite_kg');

        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.mouvements.store', $fiche), [
                'type' => 'perte',
                'quantite_kg' => 80,
                'moved_at' => Carbon::today()->toDateString(),
            ])
            ->assertSessionHasErrors('quantite_kg');

        $this->assertSame(50.0, $fiche->refresh()->disponible());
    }

    public function test_la_prevision_utilise_les_sorties_et_les_recoltes_planifiees(): void
    {
        $this->producteur->update(['ville' => null, 'latitude' => null, 'longitude' => null]);
        $fiche = $this->ouvrir(['quantite_initiale' => 40, 'seuil_kg' => 5]);
        $this->mouvement($fiche, 'sortie', 10, Carbon::today()->subDays(10));
        $this->mouvement($fiche, 'sortie', 10, Carbon::today());
        $this->recolte([
            'planned_harvest_at' => Carbon::today()->addDays(5)->toDateString(),
            'estimated_kg' => 30,
        ]);

        $rupture = Carbon::today()->addDays(25)->format('d/m/Y');

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('20 kg')
            ->assertSee('36 kg')
            ->assertSee('22 kg')
            ->assertSee($rupture)
            ->assertSee('graphique-stock')
            ->assertSee('Récolte planifiée de 30 kg')
            ->assertDontSee('Aucune date de rupture');

        $this->assertSame(20.0, $fiche->refresh()->disponible());
    }

    public function test_sans_historique_aucune_date_de_rupture_n_est_inventee(): void
    {
        $this->producteur->update(['ville' => null, 'latitude' => null, 'longitude' => null]);
        $fiche = $this->ouvrir(['quantite_initiale' => 15, 'seuil_kg' => 5]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('Historique insuffisant')
            ->assertSee('Aucune date de rupture');
    }

    public function test_la_meteo_signale_un_risque_sur_une_recolte_proche(): void
    {
        $jour = Carbon::today()->addDay()->toDateString();
        $fiche = $this->ouvrir(['quantite_initiale' => 12, 'seuil_kg' => 5]);
        $this->recolte([
            'planned_harvest_at' => $jour,
            'estimated_kg' => 30,
        ]);
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
                    'temperature_2m_max' => [22],
                    'precipitation_sum' => [30],
                    'wind_speed_10m_max' => [10],
                    'weather_code' => [61],
                ],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.stocks.show', $fiche))
            ->assertOk()
            ->assertSee('Risque de fortes pluies')
            ->assertSee('12 kg');

        $this->assertSame(12.0, $fiche->refresh()->disponible());
    }

    public function test_un_autre_producteur_ne_voit_pas_le_stock_et_une_culture_manuelle_reste_libre(): void
    {
        $fiche = $this->ouvrir();
        $autre = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        Product::factory()->create(['user_id' => $autre->id, 'name' => 'Orange maltaise']);

        $this->actingAs($autre)->get(route('acteur.stocks.show', $fiche))->assertForbidden();

        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.store'), [
                'culture' => 'Pastèque',
                'seuil_kg' => 10,
            ])
            ->assertRedirect();

        $manuelle = StockItem::query()->where('culture_name', 'Pastèque')->firstOrFail();
        $this->assertNull($manuelle->product_id);

        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.store'), [
                'culture' => 'Orange maltaise',
                'seuil_kg' => 10,
            ])
            ->assertRedirect();

        $this->assertNull(StockItem::query()->where('culture_name', 'Orange maltaise')->value('product_id'));
    }

    public function test_un_transformateur_ne_voit_pas_les_stocks(): void
    {
        $transformateur = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'transformateur',
        ]);

        $this->actingAs($transformateur)->get(route('acteur.stocks.index'))->assertForbidden();
    }

    public function test_l_admin_voit_les_ruptures_par_region_et_les_alertes(): void
    {
        $jour = Carbon::today()->addDay()->toDateString();
        $this->ouvrir(['seuil_kg' => 20]);
        $this->recolte([
            'planned_harvest_at' => $jour,
            'estimated_kg' => 40,
        ]);

        $gafsa = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'producteur',
            'ville' => 'Gafsa',
            'latitude' => null,
            'longitude' => null,
        ]);
        $orange = Product::factory()->create([
            'user_id' => $gafsa->id,
            'name' => 'Orange de Gafsa',
        ]);
        StockItem::query()->create([
            'user_id' => $gafsa->id,
            'product_id' => $orange->id,
            'culture_name' => 'Orange de Gafsa',
            'seuil_kg' => 10,
        ]);
        StockItem::query()->where('culture_name', 'Orange de Gafsa')->first()->movements()->create([
            'type' => StockMovement::ENTREE,
            'quantite_kg' => 80,
            'source' => StockMovement::MANUEL,
            'motif' => 'Stock initial',
            'moved_at' => Carbon::today()->toDateString(),
        ]);

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
                    'temperature_2m_max' => [22],
                    'precipitation_sum' => [30],
                    'wind_speed_10m_max' => [10],
                    'weather_code' => [61],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.stocks.index', ['region' => 'Nabeul']))
            ->assertOk()
            ->assertSee('Tomate du Sahel')
            ->assertSee('Nabeul')
            ->assertSee('Rupture')
            ->assertSee('Risque de fortes pluies')
            ->assertSee('graphique-produits')
            ->assertDontSee('80 kg');
    }

    /**
     * @param  array<string, mixed>  $remplacement
     */
    private function ouvrir(array $remplacement = []): StockItem
    {
        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.store'), array_merge([
                'culture' => 'Tomate du Sahel',
                'seuil_kg' => 20,
            ], $remplacement))
            ->assertRedirect();

        return StockItem::query()->where('culture_name', 'Tomate du Sahel')->firstOrFail();
    }

    private function mouvement(StockItem $fiche, string $type, float $kg, Carbon $date): void
    {
        $this->actingAs($this->producteur)
            ->post(route('acteur.stocks.mouvements.store', $fiche), [
                'type' => $type,
                'quantite_kg' => $kg,
                'moved_at' => $date->toDateString(),
                'motif' => 'Vente',
            ])
            ->assertRedirect();
    }

    private function commande(string $statut, int $quantite): Commande
    {
        $commande = Commande::query()->create([
            'nom' => 'Ben',
            'prenom' => 'Ali',
            'email' => 'ali@example.test',
            'telephone' => '20000000',
            'adresse' => 'Rue 1',
            'ville' => 'Tunis',
            'total' => $quantite * 2,
            'statut' => $statut,
            'date_commande' => Carbon::today(),
        ]);
        $commande->products()->attach($this->tomate->id, [
            'quantite' => $quantite,
            'prix_unitaire' => 2,
            'sous_total' => $quantite * 2,
        ]);

        return $commande;
    }

    /**
     * @param  array<string, mixed>  $remplacement
     */
    private function recolte(array $remplacement = []): Harvest
    {
        return Harvest::query()->create(array_merge([
            'user_id' => $this->producteur->id,
            'product_id' => $this->tomate->id,
            'culture_name' => 'Tomate du Sahel',
            'planted_at' => Carbon::today()->subDays(80)->toDateString(),
            'area_hectares' => 1.5,
            'estimated_kg' => 400,
            'harvested_kg' => null,
            'planned_harvest_at' => Carbon::today()->addDays(20)->toDateString(),
            'status' => Harvest::PLANIFIEE,
        ], $remplacement));
    }
}
