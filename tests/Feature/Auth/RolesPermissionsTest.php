<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rôles et permissions Spatie, alignés sur users.role / users.type_acteur.
 */
class RolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_le_role_spatie_suit_le_role_et_le_type_d_acteur(): void
    {
        $this->assertTrue(User::factory()->create(['role' => 'admin'])->hasRole('admin'));
        $this->assertTrue(User::factory()->create(['role' => 'consommateur'])->hasRole('consommateur'));

        $acteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $this->assertTrue($acteur->hasRole('producteur'));

        $acteur->update(['type_acteur' => 'distributeur']);
        $this->assertTrue($acteur->fresh()->hasRole('distributeur'));
        $this->assertFalse($acteur->fresh()->hasRole('producteur'));
    }

    public function test_un_producteur_n_a_pas_la_permission_gerer_categories(): void
    {
        $producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);

        $this->assertFalse($producteur->can('gerer-categories'));
        $this->assertTrue($producteur->can('gerer-lots'));
    }

    public function test_l_admin_a_toutes_les_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($admin->can('gerer-categories'));
        $this->assertTrue($admin->can('valider-certifications'));
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
    }

    public function test_la_route_des_categories_verifie_la_permission_spatie(): void
    {
        // Admin par sa colonne « role », mais sans la permission Spatie : refusé par le middleware « permission »
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles([]);

        $this->actingAs($admin->fresh())->get(route('admin.categories.index'))->assertForbidden();
    }

    public function test_un_consommateur_n_accede_pas_a_la_gestion_des_lots(): void
    {
        $client = User::factory()->create(['role' => 'consommateur']);

        $this->actingAs($client)->get(route('gestion.lots.index'))->assertForbidden();
    }
}
