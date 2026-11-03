<?php

namespace Tests\Feature\ReclamationsAvis;

use App\Models\AlerteQualite;
use App\Models\Avis;
use App\Notifications\RappelProduitNotification;
use App\Services\AlerteQualiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlerteQualiteTest extends TestCase
{
    use DonneesModule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerModule();
    }

    private function signalerProbleme(): void
    {
        $lot = $this->creerLot($this->produit);
        $this->creerReclamation($this->client, 'Oranges moisies', ['categorie' => 'qualite', 'lot_id' => $lot->id]);
        $this->creerReclamation($this->autreClient, 'Goût bizarre et moisissure', ['categorie' => 'securite_alimentaire', 'lot_id' => $lot->id]);
        Avis::factory()->create(['produit_id' => $this->produit->id, 'note' => 1, 'commentaire' => 'Oranges moisies à l\'intérieur.']);
    }

    public function test_sous_le_seuil_aucune_alerte(): void
    {
        $this->creerReclamation($this->client, 'Un seul souci', ['categorie' => 'qualite']);

        $this->assertCount(0, app(AlerteQualiteService::class)->detecter());
    }

    public function test_une_question_ou_un_retard_ne_compte_pas(): void
    {
        foreach (range(1, 4) as $i) {
            $this->creerReclamation($this->client, 'Retard '.$i, ['categorie' => 'livraison']);
        }

        $this->assertCount(0, app(AlerteQualiteService::class)->detecter());
    }

    public function test_plusieurs_signalements_creent_une_alerte_avec_le_lot(): void
    {
        $this->signalerProbleme();

        $alertes = app(AlerteQualiteService::class)->detecter();

        $this->assertCount(1, $alertes);
        $alerte = $alertes->first();
        $this->assertSame(3, $alerte->nb_signaux);
        $this->assertSame('elevee', $alerte->gravite); // risque sanitaire évoqué
        $this->assertTrue($alerte->rappel_conseille);
        $this->assertNotNull($alerte->lot_id);

        // Relancer la détection ne crée pas de doublon
        app(AlerteQualiteService::class)->detecter();
        $this->assertSame(1, AlerteQualite::query()->count());

        $this->actingAs($this->admin)->get(route('admin.alertes.index'))->assertOk()->assertSee('3 signalements sur Oranges Maltaises');
    }

    public function test_analyse_deepseek_de_l_alerte(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        Http::fake(['api.deepseek.com/*' => Http::response($this->reponseDeepSeek(['content' => json_encode([
            'meme_probleme' => true,
            'titre' => 'Moisissure sur le lot LOT-2026-0901',
            'resume' => 'Trois clients décrivent des oranges moisies reçues cette semaine.',
            'gravite' => 'elevee',
            'recommandation' => 'Bloquer le lot et contacter le producteur.',
            'rappel_conseille' => true,
            'motif_rappel' => 'Présence de moisissures signalée par plusieurs clients.',
        ])]))]);

        $this->signalerProbleme();
        $alerte = app(AlerteQualiteService::class)->detecter()->first();

        $this->actingAs($this->admin)->post(route('admin.alertes.analyser', $alerte))->assertRedirect();

        $alerte->refresh();
        $this->assertTrue($alerte->analyse_ia);
        $this->assertSame('Moisissure sur le lot LOT-2026-0901', $alerte->titre);
        $this->assertStringContainsString('moisissures', $alerte->motif_rappel);
    }

    public function test_declencher_le_rappel_du_lot(): void
    {
        Notification::fake();
        $this->signalerProbleme();
        $alerte = app(AlerteQualiteService::class)->detecter()->first();

        $this->actingAs($this->admin)
            ->post(route('admin.alertes.rappel', $alerte), ['lot_id' => $alerte->lot_id, 'motif_rappel' => 'Moisissures signalées par plusieurs clients.'])
            ->assertRedirect(route('admin.alertes.index'));

        $this->assertSame('rappele', $alerte->lot->fresh()->statut);
        $this->assertSame('rappel_declenche', $alerte->fresh()->statut);
        Notification::assertSentTo([$this->client, $this->autreClient], RappelProduitNotification::class);
        $this->assertDatabaseHas('historique_reclamations', ['type' => 'rappel']);

        // Alerte traitée : les mêmes signaux ne relancent pas d'alerte
        $this->assertCount(0, app(AlerteQualiteService::class)->detecter());
    }

    public function test_le_rappel_refuse_un_lot_d_un_autre_produit(): void
    {
        $this->signalerProbleme();
        $alerte = app(AlerteQualiteService::class)->detecter()->first();
        $autreProduit = $this->produit->replicate()->fill(['name' => 'Citrons']);
        $autreProduit->save();
        $lotEtranger = $this->creerLot($autreProduit, 'LOT-2026-0999');

        $this->actingAs($this->admin)
            ->post(route('admin.alertes.rappel', $alerte), ['lot_id' => $lotEtranger->id, 'motif_rappel' => 'Motif suffisamment long.'])
            ->assertSessionHasErrors('lot_id');
    }

    public function test_classer_une_alerte(): void
    {
        $this->signalerProbleme();
        $alerte = app(AlerteQualiteService::class)->detecter()->first();

        $this->actingAs($this->admin)->patch(route('admin.alertes.classer', $alerte))->assertRedirect();

        $this->assertSame('classee', $alerte->fresh()->statut);
        $this->assertSame($this->admin->id, $alerte->fresh()->traitee_par);
    }

    public function test_la_commande_artisan_detecte_les_alertes(): void
    {
        $this->signalerProbleme();

        $this->artisan('reclamations:alertes')->expectsOutputToContain('1 alerte(s) ouverte(s).')->assertSuccessful();
    }
}
