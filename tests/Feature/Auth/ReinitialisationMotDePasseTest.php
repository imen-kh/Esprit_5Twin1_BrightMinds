<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ReinitialisationMotDePasse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Mot de passe oublié : lien par email (broker natif) puis nouveau mot de passe.
 */
class ReinitialisationMotDePasseTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_mot_de_passe_oublie_s_affiche(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_le_lien_de_reinitialisation_est_envoye_par_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ReinitialisationMotDePasse::class);
    }

    public function test_une_adresse_inconnue_est_signalee(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'inconnu@example.tn'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_reinitialisation_avec_un_jeton_valide(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $jeton = null;
        Notification::assertSentTo($user, ReinitialisationMotDePasse::class, function ($notification) use (&$jeton) {
            $jeton = $notification->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $jeton, 'email' => $user->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $jeton,
            'email' => $user->email,
            'password' => 'Nouveau456',
            'password_confirmation' => 'Nouveau456',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('Nouveau456', $user->fresh()->password));
    }

    public function test_un_jeton_invalide_est_refuse(): void
    {
        $user = User::factory()->create();

        $this->from(route('password.request'))->post(route('password.update'), [
            'token' => 'jeton-invente',
            'email' => $user->email,
            'password' => 'Nouveau456',
            'password_confirmation' => 'Nouveau456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_un_mot_de_passe_trop_faible_est_refuse(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'jeton',
            'email' => $user->email,
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertSessionHasErrors('password');
    }
}
