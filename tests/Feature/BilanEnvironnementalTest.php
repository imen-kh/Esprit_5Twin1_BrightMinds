<?php

namespace Tests\Feature;

use App\Models\BilanEnvironnemental;
use App\Models\Category;
use App\Models\FacteurEmission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BilanEnvironnementalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private FacteurEmission $facteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $category = Category::query()->create([
            'name' => 'Cat '.uniqid(),
            'description' => 'Catégorie de test',
        ]);

        $this->product = Product::query()->create([
            'name' => 'Tomate test '.uniqid(),
            'description' => 'Produit de test',
            'price' => 3.500,
            'status' => 'published',
            'category_id' => $category->id,
            'user_id' => $this->admin->id,
        ]);

        $this->facteur = FacteurEmission::query()->create([
            'nom' => 'Camion diesel test',
            'code' => 'TEST_'.uniqid(),
            'categorie' => 'transport',
            'valeur' => 0.1620,
            'unite' => 'kg CO2e / t.km',
            'actif' => true,
        ]);
    }

    public function test_index_create_show_et_edit_sont_accessibles(): void
    {
        $bilan = $this->creerBilan();

        $this->actingAs($this->admin)
            ->get(route('admin.bilans-environnementaux.index'))
            ->assertOk()
            ->assertSee('Ajouter un bilan environnemental')
            ->assertSee($this->product->name)
            ->assertSee($this->facteur->nom);

        $this->actingAs($this->admin)
            ->get(route('admin.bilans-environnementaux.create'))
            ->assertOk()
            ->assertSee('Sélectionner un produit')
            ->assertSee($this->product->name)
            ->assertSee($this->facteur->nom);

        $this->actingAs($this->admin)
            ->get(route('admin.bilans-environnementaux.show', $bilan))
            ->assertOk()
            ->assertSee($this->product->name)
            ->assertSee($this->facteur->nom)
            ->assertSee('Modifier')
            ->assertSee('Supprimer')
            ->assertSee('Voir sur le site');

        $this->actingAs($this->admin)
            ->get(route('admin.bilans-environnementaux.edit', $bilan))
            ->assertOk()
            ->assertSee($this->product->name)
            ->assertSee('value="'.$this->product->id.'"', false);
    }

    public function test_store_enregistre_le_bilan_et_calcule_le_score(): void
    {
        $avant = BilanEnvironnemental::query()->count();

        $this->actingAs($this->admin)
            ->post(route('admin.bilans-environnementaux.store'), $this->payload())
            ->assertRedirect(route('admin.bilans-environnementaux.index'))
            ->assertSessionHas('success');

        $this->assertSame($avant + 1, BilanEnvironnemental::query()->count());

        $bilan = BilanEnvironnemental::query()->where('produit_id', $this->product->id)->first();

        $this->assertNotNull($bilan);
        $this->assertSame($this->product->name, $bilan->nom_produit);
        $this->assertSame($this->facteur->id, $bilan->facteur_emission_id);
        $this->assertNotNull($bilan->score_numerique);
        $this->assertGreaterThanOrEqual(0, $bilan->score_numerique);
        $this->assertLessThanOrEqual(100, $bilan->score_numerique);
        $this->assertNotNull($bilan->co2_total);
    }

    public function test_store_refuse_un_produit_inexistant(): void
    {
        $avant = BilanEnvironnemental::query()->count();

        $this->actingAs($this->admin)
            ->from(route('admin.bilans-environnementaux.create'))
            ->post(route('admin.bilans-environnementaux.store'), $this->payload([
                'produit_id' => 99999999,
            ]))
            ->assertRedirect(route('admin.bilans-environnementaux.create'))
            ->assertSessionHasErrors('produit_id');

        $this->assertSame($avant, BilanEnvironnemental::query()->count());
    }

    public function test_store_refuse_un_facteur_inexistant(): void
    {
        $avant = BilanEnvironnemental::query()->count();

        $this->actingAs($this->admin)
            ->from(route('admin.bilans-environnementaux.create'))
            ->post(route('admin.bilans-environnementaux.store'), $this->payload([
                'facteur_emission_id' => 99999999,
            ]))
            ->assertRedirect(route('admin.bilans-environnementaux.create'))
            ->assertSessionHasErrors('facteur_emission_id');

        $this->assertSame($avant, BilanEnvironnemental::query()->count());
    }

    public function test_store_refuse_des_donnees_invalides(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.bilans-environnementaux.create'))
            ->post(route('admin.bilans-environnementaux.store'), $this->payload([
                'quantite_produit' => 0,
                'statut' => 'inconnu',
            ]))
            ->assertRedirect(route('admin.bilans-environnementaux.create'))
            ->assertSessionHasErrors(['quantite_produit', 'statut']);
    }

    public function test_update_modifie_le_bilan_sans_en_creer_un_autre(): void
    {
        $bilan = $this->creerBilan();
        $avant = BilanEnvironnemental::query()->count();

        $this->actingAs($this->admin)
            ->put(route('admin.bilans-environnementaux.update', $bilan), $this->payload([
                'commentaires' => 'Commentaire mis à jour',
                'consommation_eau_l' => 80,
                'score_numerique' => 82,
                'eco_score' => 'B',
            ]))
            ->assertRedirect(route('admin.bilans-environnementaux.show', $bilan))
            ->assertSessionHas('success');

        $this->assertSame($avant, BilanEnvironnemental::query()->count());

        $bilan->refresh();
        $this->assertSame('Commentaire mis à jour', $bilan->commentaires);
        $this->assertEquals(80, (float) $bilan->consommation_eau_l);
        $this->assertSame($this->product->id, $bilan->produit_id);
    }

    public function test_patch_met_aussi_a_jour_le_bilan(): void
    {
        $bilan = $this->creerBilan();

        $this->actingAs($this->admin)
            ->patch(route('admin.bilans-environnementaux.update', $bilan), $this->payload([
                'commentaires' => 'Via patch',
                'score_numerique' => 70,
                'eco_score' => 'C',
            ]))
            ->assertRedirect(route('admin.bilans-environnementaux.show', $bilan));

        $this->assertSame('Via patch', $bilan->refresh()->commentaires);
    }

    public function test_destroy_supprime_le_bilan(): void
    {
        $bilan = $this->creerBilan();

        $this->actingAs($this->admin)
            ->delete(route('admin.bilans-environnementaux.destroy', $bilan))
            ->assertRedirect(route('admin.bilans-environnementaux.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bilans_environnementaux', ['id' => $bilan->id]);
    }

    public function test_relations_produit_et_facteur_emission(): void
    {
        $bilan = $this->creerBilan();

        $this->assertTrue($this->product->bilansEnvironnementaux()->whereKey($bilan->id)->exists());
        $this->assertTrue($this->facteur->bilansEnvironnementaux()->whereKey($bilan->id)->exists());
        $this->assertTrue($bilan->product->is($this->product));
        $this->assertTrue($bilan->facteurEmission->is($this->facteur));
    }

    public function test_appreciation_du_score(): void
    {
        $bilan = new BilanEnvironnemental;

        $bilan->score_numerique = 95;
        $this->assertSame('Excellent', $bilan->scoreAppreciation());

        $bilan->score_numerique = 80;
        $this->assertSame('Bon', $bilan->scoreAppreciation());

        $bilan->score_numerique = 60;
        $this->assertSame('Moyen', $bilan->scoreAppreciation());

        $bilan->score_numerique = 20;
        $this->assertSame('Faible', $bilan->scoreAppreciation());
    }

    public function test_pages_publiques_liste_detail_simulateur_et_fiche_produit(): void
    {
        $bilan = $this->creerBilan();

        $this->get(route('front.environnement.index'))
            ->assertOk()
            ->assertSee($this->product->name)
            ->assertSee($this->facteur->nom);

        $this->get(route('front.environnement.show', $bilan))
            ->assertOk()
            ->assertSee($this->product->name)
            ->assertSee('Impact transport');

        $this->postJson(route('front.environnement.simuler'), [
            'nom_produit' => 'Tomate de test',
            'distance_km' => 40,
            'mode_transport' => 'camion',
            'mode_culture' => 'bio',
            'type_emballage' => 'vrac',
            'quantite_produit' => 1,
        ])->assertOk()->assertJsonPath('success', true);

        // La fiche produit est réservée aux visiteurs connectés (middleware « compte »)
        $this->actingAs(User::factory()->create(['role' => 'consommateur']))
            ->get(route('produit.detail', $this->product))
            ->assertOk()
            ->assertSee('Bilan environnemental')
            ->assertSee(route('front.environnement.show', $bilan), false);
    }

    public function test_un_bilan_non_valide_reste_prive(): void
    {
        $bilan = $this->creerBilan();
        $bilan->update(['statut' => 'en_attente']);

        $this->get(route('front.environnement.show', $bilan))->assertNotFound();
        $this->get(route('front.environnement.index'))->assertDontSee($bilan->nom_produit);
    }

    public function test_la_repartition_co2_ne_depasse_pas_100(): void
    {
        $vide = new BilanEnvironnemental([
            'co2_production' => null,
            'co2_transport' => null,
            'co2_emballage' => null,
        ]);
        $this->assertSame(0, array_sum($vide->repartitionCo2()));

        $plein = new BilanEnvironnemental([
            'co2_production' => 0.5,
            'co2_transport' => 0.3,
            'co2_emballage' => 0.2,
            'co2_total' => 0,
        ]);
        $parts = $plein->repartitionCo2();

        $this->assertSame(100, array_sum($parts));
        $this->assertGreaterThanOrEqual(0, min($parts));
    }

    public function test_le_crud_front_est_reserve_a_l_administrateur(): void
    {
        $bilan = $this->creerBilan();

        $this->get(route('front.environnement.create'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->admin)
            ->get(route('front.environnement.create'))
            ->assertOk()
            ->assertSee('Nouveau bilan environnemental');

        $this->actingAs($this->admin)
            ->post(route('front.environnement.store'), $this->payload([
                'commentaires' => 'Créé depuis le front',
            ]))
            ->assertRedirect(route('front.environnement.index'))
            ->assertSessionHas('success');

        $cree = BilanEnvironnemental::query()->where('commentaires', 'Créé depuis le front')->first();
        $this->assertNotNull($cree);

        $this->actingAs($this->admin)
            ->get(route('front.environnement.edit', $bilan))
            ->assertOk()
            ->assertSee('Modifier le bilan environnemental');

        $this->actingAs($this->admin)
            ->put(route('front.environnement.update', $bilan), $this->payload([
                'commentaires' => 'Modifié depuis le front',
                'score_numerique' => 80,
                'eco_score' => 'B',
            ]))
            ->assertRedirect(route('front.environnement.show', $bilan));

        $this->assertSame('Modifié depuis le front', $bilan->refresh()->commentaires);

        $this->actingAs($this->admin)
            ->delete(route('front.environnement.destroy', $cree))
            ->assertRedirect(route('front.environnement.index'));

        $this->assertDatabaseMissing('bilans_environnementaux', ['id' => $cree->id]);
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('admin.bilans-environnementaux.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'produit_id' => $this->product->id,
            'facteur_emission_id' => $this->facteur->id,
            'quantite_produit' => 1,
            'unite_mesure' => 'kg',
            'origine_geographique' => 'Cap Bon, Tunisie',
            'distance_km' => 40,
            'mode_transport' => 'camion',
            'mode_culture' => 'bio',
            'type_emballage' => 'vrac',
            'poids_emballage_g' => 0,
            'consommation_eau_l' => 50,
            'statut' => 'valide',
            'commentaires' => 'Bilan de test',
        ], $overrides);
    }

    private function creerBilan(): BilanEnvironnemental
    {
        return BilanEnvironnemental::query()->create([
            'produit_id' => $this->product->id,
            'nom_produit' => $this->product->name,
            'facteur_emission_id' => $this->facteur->id,
            'quantite_produit' => 1,
            'unite_mesure' => 'kg',
            'consommation_eau_l' => 50,
            'co2_transport' => 0.120,
            'co2_total' => 0.850,
            'score_numerique' => 82,
            'eco_score' => 'B',
            'statut' => 'valide',
            'commentaires' => 'Commentaire initial',
        ]);
    }
}
