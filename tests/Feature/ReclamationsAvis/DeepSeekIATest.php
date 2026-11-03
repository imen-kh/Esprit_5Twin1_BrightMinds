<?php

namespace Tests\Feature\ReclamationsAvis;

use App\Models\Avis;
use App\Models\Reclamation;
use App\Services\AiReclamationService;
use App\Services\AvisIAService;
use App\Services\DeepSeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fonctions IA du module (DeepSeek simulé avec Http::fake) et replis locaux.
 */
class DeepSeekIATest extends TestCase
{
    use DonneesModule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerModule();
        config(['services.deepseek.key' => 'cle-de-test']);
    }

    public function test_sans_cle_aucun_appel_n_est_fait(): void
    {
        config(['services.deepseek.key' => null]);
        Http::fake();

        $resultat = app(AiReclamationService::class)->analyserReclamation('Colis', 'Le colis est arrivé cassé.');

        $this->assertSame('regles', $resultat['source']);
        Http::assertNothingSent();
    }

    public function test_l_analyse_deepseek_est_utilisee_et_validee(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => "```json\n".json_encode([
            'sentiment' => 'negatif',
            'priorite' => 'urgente',
            'categorie' => 'securite_alimentaire',
            'langue' => 'tn',
            'resume' => 'Le client a été malade après avoir mangé les oranges.',
            'suggestion' => 'Asslema, ma tekelch el produit w khalli el emballage.',
        ])."\n```"]))]);

        $resultat = app(AiReclamationService::class)->analyserReclamation('Mridh', 'Klit el bordgen w mridht barcha', $this->produit);

        $this->assertSame('ia', $resultat['source']);
        $this->assertSame('urgente', $resultat['priorite']);
        $this->assertSame('securite_alimentaire', $resultat['categorie']);
        $this->assertSame('tn', $resultat['langue']);

        Http::assertSent(fn (Request $requete) => $requete->hasHeader('Authorization', 'Bearer cle-de-test')
            && $requete['model'] === 'deepseek-chat'
            && $requete['response_format']['type'] === 'json_object');
    }

    public function test_une_valeur_inventee_par_le_modele_est_ecartee(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'sentiment' => 'furieux', 'priorite' => 'critique', 'categorie' => 'meteo', 'langue' => 'fr', 'resume' => 'Résumé', 'suggestion' => 'Bonjour',
        ])]))]);

        $resultat = app(AiReclamationService::class)->analyserReclamation('Retard', 'Ma livraison a une semaine de retard.');

        $this->assertSame('negatif', $resultat['sentiment']);
        $this->assertSame('haute', $resultat['priorite']);
        $this->assertSame('livraison', $resultat['categorie']);
    }

    public function test_une_panne_de_l_api_bascule_sur_les_regles_et_met_en_pause(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response('erreur', 500)]);

        $resultat = app(AiReclamationService::class)->analyserReclamation('Colis', 'Le colis est arrivé cassé.');

        $this->assertSame('regles', $resultat['source']);
        $this->assertTrue(Cache::has('deepseek:pause'));
        $this->assertFalse(app(DeepSeekService::class)->estDisponible());
    }

    public function test_le_copilote_utilise_deepseek(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'message' => "Bonjour Sana,\nNous vous proposons le remplacement de vos oranges.\nL'équipe NutriTrace",
            'solution' => 'Remplacement du produit',
        ])]))]);

        $reclamation = $this->creerReclamation($this->client, 'Oranges abîmées', ['categorie' => 'qualite']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.reclamations.copilote', $reclamation), ['ton' => 'empathique'])
            ->assertOk()
            ->assertJsonPath('source', 'DeepSeek')
            ->assertJsonPath('solution', 'Remplacement du produit');
    }

    public function test_qui_est_responsable_enregistre_le_maillon(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'responsable' => 'distributeur', 'confiance' => 82, 'explication' => 'Le transport a duré 3 jours à 14 °C.',
        ])]))]);

        $reclamation = $this->creerReclamation($this->client, 'Oranges molles', ['lot_id' => $this->creerLot($this->produit)->id]);

        $this->actingAs($this->admin)->post(route('admin.reclamations.responsabilite', $reclamation))->assertRedirect();

        $reclamation->refresh();
        $this->assertSame('distributeur', $reclamation->responsable_suggere);
        $this->assertStringContainsString('14 °C', $reclamation->responsable_explication);
        $this->assertDatabaseHas('historique_reclamations', ['reclamation_id' => $reclamation->id, 'type' => 'responsable']);
    }

    public function test_qui_est_responsable_sans_ia_utilise_la_categorie(): void
    {
        config(['services.deepseek.key' => null]);
        $reclamation = $this->creerReclamation($this->client, 'Carton', ['categorie' => 'emballage']);

        $resultat = app(AiReclamationService::class)->analyserResponsabilite($reclamation);

        $this->assertSame('distributeur', $resultat['responsable']);
        $this->assertSame('regles', $resultat['source']);
    }

    public function test_la_moderation_deepseek_retient_un_avis(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'decision' => 'moderer', 'raison' => 'Publicité pour un autre site', 'sentiment' => 'neutre',
            'aspects' => ['prix' => 2, 'inconnu' => 5], 'probleme_detecte' => false,
        ])]))]);

        $resultat = app(AvisIAService::class)->analyser(3, null, 'Achetez plutôt chez mon voisin, bien moins cher.', $this->produit);

        $this->assertSame('moderer', $resultat['decision']);
        $this->assertSame('Publicité pour un autre site', $resultat['raison']);
        $this->assertSame(['prix' => 2], $resultat['aspects']);
    }

    public function test_la_synthese_des_avis_est_mise_en_cache(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'resume' => 'Les clients apprécient le goût, mais l\'emballage déçoit.',
            'points_forts' => ['Goût sucré'], 'points_faibles' => ['Emballage fragile'],
        ])]))]);

        Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id, 'note' => 5]);
        Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->autreClient->id, 'note' => 2]);

        $service = app(AvisIAService::class);
        $premiere = $service->synthese($this->produit);
        $seconde = $service->synthese($this->produit);

        $this->assertSame('ia', $premiere['source']);
        $this->assertSame(['Goût sucré'], $premiere['points_forts']);
        $this->assertSame($premiere, $seconde);
        Http::assertSentCount(1);
    }

    public function test_reanalyser_met_a_jour_le_dossier(): void
    {
        config(['services.deepseek.key' => null]);
        $reclamation = $this->creerReclamation($this->client, 'Question', ['description' => 'Quelle est l\'origine des oranges ?']);

        $this->actingAs($this->admin)->post(route('admin.reclamations.reanalyser', $reclamation))->assertRedirect();

        $this->assertSame('information', Reclamation::query()->find($reclamation->id)->categorie);
    }
}
