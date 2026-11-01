<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Double authentification TOTP : activation, confirmation, challenge au login,
 * codes de récupération à usage unique et désactivation.
 */
class DoubleAuthentificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->user = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
    }

    /**
     * Code TOTP de l'utilisateur, calculé comme le ferait Google Authenticator.
     * $decalage : en périodes de 30 s (1 = période suivante, acceptée par la tolérance), pour ne pas rejouer un code déjà utilisé.
     */
    private function codeTotp(int $decalage = 0): string
    {
        $google2fa = new Google2FA;

        return $google2fa->oathTotp($this->user->fresh()->two_factor_secret, $google2fa->getTimestamp() + $decalage);
    }

    /**
     * Active et confirme la 2FA ; renvoie les codes de récupération affichés.
     *
     * @return list<string>
     */
    private function activer(): array
    {
        $this->actingAs($this->user)->post(route('profil.two-factor.activer'), ['password' => 'password']);
        $this->actingAs($this->user)->post(route('profil.two-factor.confirmer'), ['code' => $this->codeTotp()]);

        return session('codes_recuperation');
    }

    private function seConnecterAvecLeMotDePasse(): void
    {
        auth()->logout();
        $this->post(route('login.submit'), ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_l_activation_affiche_le_qr_code_et_la_cle(): void
    {
        $this->actingAs($this->user)
            ->post(route('profil.two-factor.activer'), ['password' => 'password'])
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->doubleAuthentificationEnAttente());

        $this->actingAs($this->user)->get(route('profil.edit'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('Confirmer et activer');
    }

    public function test_l_activation_exige_le_mot_de_passe(): void
    {
        $this->actingAs($this->user)
            ->post(route('profil.two-factor.activer'), ['password' => 'mauvais'])
            ->assertSessionHasErrors('password', null, 'deuxFacteurs');

        $this->assertNull($this->user->fresh()->two_factor_secret);
    }

    public function test_la_cle_secrete_est_chiffree_en_base(): void
    {
        $this->actingAs($this->user)->post(route('profil.two-factor.activer'), ['password' => 'password']);

        $brut = DB::table('users')->where('id', $this->user->id)->value('two_factor_secret');

        $this->assertNotSame($this->user->fresh()->two_factor_secret, $brut);
    }

    public function test_la_confirmation_avec_un_code_totp_genere_8_codes_de_recuperation(): void
    {
        $this->actingAs($this->user)->post(route('profil.two-factor.activer'), ['password' => 'password']);

        $this->actingAs($this->user)
            ->post(route('profil.two-factor.confirmer'), ['code' => '000000'])
            ->assertSessionHasErrors('code', null, 'deuxFacteurs');

        $this->actingAs($this->user)
            ->post(route('profil.two-factor.confirmer'), ['code' => $this->codeTotp()])
            ->assertSessionHas('codes_recuperation');

        $this->assertTrue($this->user->fresh()->aDoubleAuthentification());
        $this->assertCount(8, session('codes_recuperation'));
    }

    public function test_le_login_passe_par_le_challenge_et_accepte_un_code_totp(): void
    {
        $this->activer();
        $this->seConnecterAvecLeMotDePasse();

        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Vérification en deux étapes');

        $this->post(route('two-factor.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.verify'), ['code' => $this->codeTotp(1)])->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_un_code_totp_deja_utilise_ne_peut_pas_etre_rejoue(): void
    {
        $this->activer();
        $this->seConnecterAvecLeMotDePasse();

        $code = $this->codeTotp(1);
        $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($this->user);

        $this->seConnecterAvecLeMotDePasse();
        $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_un_code_de_recuperation_ne_sert_qu_une_fois(): void
    {
        $codes = $this->activer();

        $this->seConnecterAvecLeMotDePasse();
        $this->post(route('two-factor.verify'), ['recovery_code' => strtolower($codes[0])])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertAuthenticatedAs($this->user);
        $this->assertCount(7, $this->user->fresh()->two_factor_recovery_codes);

        $this->seConnecterAvecLeMotDePasse();
        $this->post(route('two-factor.verify'), ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_le_challenge_est_inaccessible_sans_mot_de_passe_valide(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_la_desactivation_exige_le_mot_de_passe(): void
    {
        $this->activer();

        $this->actingAs($this->user)
            ->delete(route('profil.two-factor.desactiver'), ['password' => 'mauvais'])
            ->assertSessionHasErrors('password', null, 'deuxFacteurs');
        $this->assertTrue($this->user->fresh()->aDoubleAuthentification());

        $this->actingAs($this->user)
            ->delete(route('profil.two-factor.desactiver'), ['password' => 'password'])
            ->assertRedirect();
        $this->assertFalse($this->user->fresh()->aDoubleAuthentification());
        $this->assertNull($this->user->fresh()->two_factor_secret);
    }
}
