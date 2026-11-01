<?php

namespace Tests\Feature\Tracabilite;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module Traçabilité : lots, étapes, droits d'accès et page publique.
 */
class LotTest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    private Product $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $this->produit = Product::factory()->create(['user_id' => $this->producteur->id]);
    }

    /**
     * Données valides d'un lot, surchargeables.
     *
     * @param  array<string, mixed>  $surcharge
     * @return array<string, mixed>
     */
    private function donneesLot(array $surcharge = []): array
    {
        return [
            'code' => 'LOT-2026-0101',
            'produit_id' => $this->produit->id,
            'date_production' => now()->subDays(3)->toDateString(),
            'date_peremption' => now()->addDays(20)->toDateString(),
            'quantite' => 250,
            'unite' => 'kg',
            'statut' => 'en_production',
            'notes' => null,
            ...$surcharge,
        ];
    }

    private function lotDu(User $proprietaire, array $attributs = []): Lot
    {
        return Lot::factory()->create([
            'produit_id' => Product::factory()->create(['user_id' => $proprietaire->id])->id,
            'date_production' => now()->subDays(5)->toDateString(),
            ...$attributs,
        ]);
    }

    public function test_un_producteur_cree_un_lot_valide(): void
    {
        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.store'), $this->donneesLot())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lots', ['code' => 'LOT-2026-0101', 'produit_id' => $this->produit->id, 'quantite' => 250]);
    }

    public function test_la_creation_refuse_des_donnees_invalides(): void
    {
        $this->actingAs($this->producteur)
            ->from(route('gestion.lots.create'))
            ->post(route('gestion.lots.store'), $this->donneesLot([
                'code' => 'MAUVAIS-CODE',
                'date_production' => now()->addDay()->toDateString(),
                'quantite' => 0,
                'unite' => 'tonnes',
            ]))
            ->assertRedirect(route('gestion.lots.create'))
            ->assertSessionHasErrors(['code', 'date_production', 'quantite', 'unite']);

        $this->assertDatabaseCount('lots', 0);
    }

    public function test_un_producteur_ne_cree_pas_de_lot_pour_le_produit_d_un_autre(): void
    {
        $autre = Product::factory()->create(['user_id' => User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur'])->id]);

        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.store'), $this->donneesLot(['produit_id' => $autre->id]))
            ->assertSessionHasErrors('produit_id');
    }

    public function test_un_rappel_sans_motif_est_refuse(): void
    {
        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.store'), $this->donneesLot(['statut' => 'rappele', 'motif_rappel' => '']))
            ->assertSessionHasErrors('motif_rappel');

        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.store'), $this->donneesLot(['statut' => 'rappele', 'motif_rappel' => 'Corps étranger détecté au contrôle.']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lots', ['code' => 'LOT-2026-0101', 'motif_rappel' => 'Corps étranger détecté au contrôle.']);
    }

    public function test_une_etape_anterieure_a_la_production_est_refusee(): void
    {
        $lot = $this->lotDu($this->producteur);

        $this->actingAs($this->producteur)
            ->post(route('gestion.etapes.store'), [
                'lot_id' => $lot->id,
                'type_etape' => 'production',
                'titre' => 'Récolte',
                'lieu' => 'Nabeul',
                'date_etape' => now()->subDays(10)->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('date_etape');

        $this->assertDatabaseCount('etape_tracabilites', 0);
    }

    public function test_une_etape_valide_est_enregistree_et_met_a_jour_le_statut(): void
    {
        $lot = $this->lotDu($this->producteur, ['statut' => 'en_production']);

        $this->actingAs($this->producteur)
            ->post(route('gestion.etapes.store'), [
                'lot_id' => $lot->id,
                'type_etape' => 'production',
                'titre' => 'Récolte des fraises',
                'lieu' => 'Nabeul',
                'latitude' => 36.4561,
                'longitude' => 10.7376,
                'temperature' => 18,
                'date_etape' => now()->subDays(4)->format('Y-m-d H:i'),
            ])
            ->assertRedirect(route('gestion.lots.show', $lot))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('etape_tracabilites', ['lot_id' => $lot->id, 'user_id' => $this->producteur->id, 'type_etape' => 'production']);
    }

    public function test_un_producteur_ne_peut_pas_declarer_une_etape_de_transport(): void
    {
        $lot = $this->lotDu($this->producteur);

        $this->actingAs($this->producteur)
            ->post(route('gestion.etapes.store'), [
                'lot_id' => $lot->id,
                'type_etape' => 'transport',
                'titre' => 'Transport',
                'lieu' => 'Tunis',
                'date_etape' => now()->subDay()->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('type_etape');
    }

    public function test_un_producteur_ne_voit_pas_les_lots_d_un_autre(): void
    {
        $voisin = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $lotDuVoisin = $this->lotDu($voisin, ['code' => 'LOT-2026-0777']);
        $monLot = $this->lotDu($this->producteur, ['code' => 'LOT-2026-0555']);

        $this->actingAs($this->producteur)->get(route('gestion.lots.index'))
            ->assertOk()
            ->assertSee($monLot->code)
            ->assertDontSee($lotDuVoisin->code);

        $this->actingAs($this->producteur)->get(route('gestion.lots.show', $lotDuVoisin))->assertForbidden();
        $this->actingAs($this->producteur)->get(route('gestion.lots.edit', $lotDuVoisin))->assertForbidden();
    }

    public function test_la_page_publique_du_lot_est_accessible_sans_connexion(): void
    {
        $lot = $this->lotDu($this->producteur, ['code' => 'LOT-2026-0042']);
        EtapeTracabilite::factory()->type('production')->a('Nabeul')->create([
            'lot_id' => $lot->id,
            'user_id' => $this->producteur->id,
            'titre' => 'Récolte manuelle',
            'date_etape' => now()->subDays(4),
        ]);

        $this->get(route('tracabilite.show', $lot))
            ->assertOk()
            ->assertSee('LOT-2026-0042')
            ->assertSee('Récolte manuelle')
            ->assertSee('Score de transparence');

        $this->get(route('tracabilite.index', ['code' => 'lot-2026-0042']))->assertRedirect(route('tracabilite.show', $lot));
        $this->get('/tracabilite/LOT-2026-9999')->assertNotFound();
    }

    public function test_un_lot_rappele_affiche_son_motif_sur_la_page_publique(): void
    {
        $lot = $this->lotDu($this->producteur, ['statut' => 'rappele', 'motif_rappel' => 'Présence de Listeria détectée.']);

        $this->get(route('tracabilite.show', $lot))
            ->assertOk()
            ->assertSee('Lot rappelé')
            ->assertSee('Présence de Listeria détectée.');
    }

    public function test_l_assistant_refuse_une_question_vide(): void
    {
        $lot = $this->lotDu($this->producteur);

        $this->postJson(route('tracabilite.assistant', $lot), ['question' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');

        $this->postJson(route('tracabilite.assistant', $lot), ['question' => str_repeat('a', 301)])
            ->assertStatus(422);
    }
}
