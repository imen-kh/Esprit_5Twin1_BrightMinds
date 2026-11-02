<?php

namespace Tests\Feature\Dashboard;

use App\Models\Commande;
use App\Models\Harvest;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TableauIntelligentTest extends TestCase
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
            'name' => 'Ferme Ben Salah',
            'ville' => 'Nabeul',
            'latitude' => null,
            'longitude' => null,
        ]);
        $this->tomate = Product::factory()->create([
            'user_id' => $this->producteur->id,
            'name' => 'Tomate du Sahel',
            'status' => Product::PUBLISHED,
            'type_produit' => 'matiere_premiere',
        ]);
    }

    public function test_le_producteur_voit_ses_indicateurs_et_pas_ceux_d_un_autre(): void
    {
        $this->stock(5, 20);
        $this->recolte();
        $commande = Commande::query()->create([
            'nom' => 'Ben',
            'prenom' => 'Ali',
            'email' => 'ali@example.test',
            'telephone' => '20000000',
            'adresse' => 'Rue 1',
            'ville' => 'Tunis',
            'total' => 6,
            'statut' => 'confirmee',
            'date_commande' => Carbon::today(),
        ]);
        $commande->products()->attach($this->tomate->id, [
            'quantite' => 3,
            'prix_unitaire' => 2,
            'sous_total' => 6,
        ]);
        Commande::query()->create([
            'nom' => 'Ben',
            'prenom' => 'Ali',
            'email' => 'ali@example.test',
            'telephone' => '20000000',
            'adresse' => 'Rue 1',
            'ville' => 'Tunis',
            'total' => 9,
            'statut' => 'annulee',
            'date_commande' => Carbon::today(),
        ])->products()->attach($this->tomate->id, [
            'quantite' => 4,
            'prix_unitaire' => 2,
            'sous_total' => 8,
        ]);

        $autre = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        Product::factory()->create(['user_id' => $autre->id, 'name' => 'Mangue secrète']);

        $this->actingAs($this->producteur)
            ->get(route('acteur.tableau'))
            ->assertOk()
            ->assertSee('Tomate du Sahel')
            ->assertSee('5 kg')
            ->assertSee('Stock faible de Tomate du Sahel')
            ->assertSee('400 kg')
            ->assertSee('6,000')
            ->assertSee('graphique-stocks')
            ->assertSee('Selon les règles')
            ->assertSee('Aucune date de rupture')
            ->assertDontSee('Mangue secrète')
            ->assertDontSee('8,000');
    }

    public function test_sans_donnees_le_tableau_reste_vide(): void
    {
        $nouveau = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'producteur',
            'ville' => null,
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->actingAs($nouveau)
            ->get(route('acteur.tableau'))
            ->assertOk()
            ->assertSee('Aucun stock suivi')
            ->assertSee('Aucune récolte planifiée')
            ->assertSee('Indiquez votre ville')
            ->assertSee('Aucune action prioritaire')
            ->assertDontSee('42 kg');
    }

    public function test_l_ia_reformule_les_chiffres_fournis_sans_les_calculer(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);
        $this->stock(5, 20);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => 'Le stock suivi est de 5 kg. Aucun autre chiffre n’est ajouté.',
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.tableau'))
            ->assertOk()
            ->assertSee('Le stock suivi est de 5 kg')
            ->assertSee('Généré par IA')
            ->assertSee('Stock faible de Tomate du Sahel');

        Http::assertSent(fn ($requete) => str_contains($requete->body(), 'N\'invente aucun chiffre')
            && str_contains($requete->body(), '5'));
    }

    public function test_la_meteo_du_tableau_affiche_les_mesures_recues(): void
    {
        $this->producteur->update(['latitude' => 36.45, 'longitude' => 10.73]);
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 21.5,
                    'relative_humidity_2m' => 55,
                    'precipitation' => 0,
                    'wind_speed_10m' => 12,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'time' => [Carbon::today()->toDateString()],
                    'temperature_2m_min' => [14],
                    'temperature_2m_max' => [24],
                    'precipitation_sum' => [0],
                    'wind_speed_10m_max' => [12],
                    'weather_code' => [0],
                ],
            ]),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.tableau'))
            ->assertOk()
            ->assertSee('21.5 °C')
            ->assertSee('Nabeul')
            ->assertSee('graphique-meteo');
    }

    public function test_un_transformateur_et_un_consommateur_ne_voient_pas_le_tableau_producteur(): void
    {
        $transformateur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'transformateur']);
        $consommateur = User::factory()->create(['role' => 'consommateur']);

        $this->actingAs($transformateur)->get(route('acteur.tableau'))->assertForbidden();
        $this->actingAs($consommateur)->get(route('acteur.tableau'))->assertForbidden();
    }

    public function test_l_admin_voit_les_stocks_critiques_les_validations_et_conserve_la_tracabilite(): void
    {
        $this->stock(0, 20);
        $jour = Carbon::today()->addDay()->toDateString();
        $this->recolte(['planned_harvest_at' => $jour, 'estimated_kg' => 40]);
        Harvest::query()->create([
            'user_id' => $this->producteur->id,
            'product_id' => $this->tomate->id,
            'culture_name' => 'Pastèque lointaine',
            'planted_at' => Carbon::today()->toDateString(),
            'area_hectares' => 1,
            'estimated_kg' => 777,
            'planned_harvest_at' => Carbon::today()->addDays(40)->toDateString(),
            'status' => Harvest::PLANIFIEE,
        ]);
        Product::factory()->create([
            'user_id' => $this->producteur->id,
            'name' => 'Dattes à valider',
            'status' => Product::PENDING,
            'ai_analysis' => ['resume' => 'Fiche cohérente, origine lisible.', 'anomalies' => [], 'corrections' => [], 'coherent' => true],
        ]);

        $gafsa = User::factory()->create([
            'role' => 'acteur',
            'type_acteur' => 'producteur',
            'ville' => 'Gafsa',
            'latitude' => null,
            'longitude' => null,
        ]);
        $orange = Product::factory()->create(['user_id' => $gafsa->id, 'name' => 'Orange de Gafsa', 'status' => Product::PUBLISHED]);
        $fiche = StockItem::query()->create([
            'user_id' => $gafsa->id,
            'product_id' => $orange->id,
            'culture_name' => 'Orange de Gafsa',
            'seuil_kg' => 10,
        ]);
        $fiche->movements()->create([
            'type' => StockMovement::ENTREE,
            'quantite_kg' => 80,
            'source' => StockMovement::MANUEL,
            'moved_at' => Carbon::today()->toDateString(),
        ]);

        $this->producteur->update(['latitude' => 36.45, 'longitude' => 10.73]);
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
            ->get(route('admin.dashboard', [
                'region' => 'Nabeul',
                'au' => Carbon::today()->addDays(10)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Dattes à valider')
            ->assertSee('Fiche cohérente, origine lisible.')
            ->assertSee('Approuver')
            ->assertSee('Rupture — Tomate du Sahel')
            ->assertSee('Risque de fortes pluies')
            ->assertSee('Lots par statut')
            ->assertSee('graphique-categories')
            ->assertSee('Selon les règles')
            ->assertDontSee('Pastèque lointaine')
            ->assertDontSee('80 kg à Gafsa');
    }

    private function stock(float $kg, float $seuil): StockItem
    {
        $fiche = StockItem::query()->create([
            'user_id' => $this->producteur->id,
            'product_id' => $this->tomate->id,
            'culture_name' => 'Tomate du Sahel',
            'seuil_kg' => $seuil,
        ]);

        if ($kg > 0) {
            $fiche->movements()->create([
                'type' => StockMovement::ENTREE,
                'quantite_kg' => $kg,
                'source' => StockMovement::MANUEL,
                'moved_at' => Carbon::today()->toDateString(),
            ]);
        }

        return $fiche;
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
            'planted_at' => Carbon::today()->subDays(70)->toDateString(),
            'area_hectares' => 1,
            'estimated_kg' => 400,
            'planned_harvest_at' => Carbon::today()->addDays(12)->toDateString(),
            'status' => Harvest::PLANIFIEE,
        ], $remplacement));
    }
}
