<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerificationAdresseEmail;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Inscription, connexion, déconnexion, vérification de l'email et accès par rôle.
 */
class AuthentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_la_base_de_test_est_sqlite_en_memoire(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_les_pages_de_connexion_et_d_inscription_s_affichent(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Mot de passe oublié ?');
        $this->get(route('register'))->assertOk();
    }

    public function test_inscription_d_un_producteur_et_envoi_de_l_email_de_verification(): void
    {
        Notification::fake();

        $this->post(route('register.submit'), [
            'name' => 'Ferme du Test',
            'email' => 'ferme@example.tn',
            'role' => 'acteur',
            'type_acteur' => 'producteur',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ferme@example.tn')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('producteur'));
        Notification::assertSentTo($user, VerificationAdresseEmail::class);
    }

    public function test_l_inscription_refuse_des_donnees_invalides(): void
    {
        User::factory()->create(['email' => 'pris@example.tn']);

        $this->from(route('register'))->post(route('register.submit'), [
            'name' => 'A',
            'email' => 'pris@example.tn',
            'role' => 'acteur',
            'password' => 'court',
            'password_confirmation' => 'different',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name', 'email', 'type_acteur', 'password']);

        $this->assertGuest();
    }

    public function test_connexion_puis_deconnexion(): void
    {
        $user = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('acteur.produits.index'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_connexion_refusee_avec_un_mauvais_mot_de_passe(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.submit'), ['email' => $user->email, 'password' => 'mauvais'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('profil.edit'))->assertRedirect(route('login'));
        $this->get(route('gestion.lots.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_un_email_non_verifie_est_renvoye_vers_la_page_de_verification(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);

        $this->actingAs($user)->get(route('profil.edit'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('gestion.lots.index'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('Vérifiez votre email');
    }

    public function test_le_lien_signe_verifie_l_adresse_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['role' => 'consommateur']);
        $user->sendEmailVerificationNotification();

        $url = null;
        Notification::assertSentTo($user, VerificationAdresseEmail::class, function ($notification) use ($user, &$url) {
            $url = $notification->toMail($user)->viewData['url'];

            return true;
        });

        $this->actingAs($user)->get($url)->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_un_producteur_recoit_une_erreur_403_sur_l_administration(): void
    {
        $producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);

        $this->actingAs($producteur)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($producteur)->get(route('admin.categories.index'))->assertForbidden();
    }
}
