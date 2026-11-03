<?php

namespace Tests\Feature;

use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Notifications\ReclamationNotification;
use App\Services\AiReclamationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\ReclamationsAvis\DonneesModule;
use Tests\TestCase;

class ReclamationTest extends TestCase
{
    use DonneesModule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerModule();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('reclamations.index'))->assertRedirect(route('login'));
    }

    public function test_un_consommateur_cree_une_reclamation(): void
    {
        $lot = $this->creerLot($this->produit);

        $this->actingAs($this->client)
            ->post(route('reclamations.store'), [
                'produit_id' => $this->produit->id,
                'commande_id' => $this->commande->id,
                'code_lot' => 'lot-2026-0901',
                'sujet' => 'Colis abîmé',
                'description' => 'Le carton est cassé et le produit est pourri. Je demande un remboursement.',
                'priorite' => 'faible',
            ])
            ->assertRedirect();

        $reclamation = Reclamation::query()->where('sujet', 'Colis abîmé')->first();

        $this->assertNotNull($reclamation);
        $this->assertSame($this->client->id, $reclamation->user_id);
        $this->assertSame($this->commande->id, $reclamation->commande_id);
        $this->assertSame($lot->id, $reclamation->lot_id);
        $this->assertSame('en_attente', $reclamation->statut);
        $this->assertSame('negatif', $reclamation->sentiment);
        $this->assertNotEmpty($reclamation->suggestion_reponse);
        // Référence lisible et délai de traitement posés à la création
        $this->assertMatchesRegularExpression('/^REC-\d{4}-\d{4}$/', $reclamation->reference);
        $this->assertNotNull($reclamation->echeance_at);
        // L'IA relève la priorité « faible » choisie par le client (remboursement, produit pourri)
        $this->assertSame('haute', $reclamation->priorite);
        $this->assertSame(2, $reclamation->historique()->count());
    }

    public function test_la_validation_refuse_une_reclamation_incomplete(): void
    {
        $this->actingAs($this->client)
            ->from(route('reclamations.create'))
            ->post(route('reclamations.store'), [
                'produit_id' => 999999,
                'sujet' => '',
                'description' => '',
                'priorite' => 'critique',
            ])
            ->assertRedirect(route('reclamations.create'))
            ->assertSessionHasErrors(['produit_id', 'sujet', 'description', 'priorite']);
    }

    public function test_on_ne_peut_pas_rattacher_la_commande_d_un_autre_client(): void
    {
        $commandeAutre = $this->creerCommande($this->autreClient, $this->produit);

        $this->actingAs($this->client)
            ->post(route('reclamations.store'), [
                'produit_id' => $this->produit->id,
                'commande_id' => $commandeAutre->id,
                'sujet' => 'Commande',
                'description' => 'Je réclame sur une commande qui n\'est pas la mienne.',
                'priorite' => 'moyenne',
            ])
            ->assertSessionHasErrors('commande_id');
    }

    public function test_un_code_de_lot_d_un_autre_produit_est_refuse(): void
    {
        $this->actingAs($this->client)
            ->post(route('reclamations.store'), [
                'produit_id' => $this->produit->id,
                'code_lot' => 'LOT-INEXISTANT',
                'sujet' => 'Lot',
                'description' => 'Le code du lot ne correspond à rien.',
                'priorite' => 'moyenne',
            ])
            ->assertSessionHasErrors('code_lot');
    }

    public function test_la_liste_affiche_les_reclamation_du_consommateur(): void
    {
        $mienne = $this->creerReclamation($this->client, 'Ma réclamation visible');
        $this->creerReclamation($this->autreClient, 'Réclamation d\'un autre');

        $this->actingAs($this->client)
            ->get(route('reclamations.index'))
            ->assertOk()
            ->assertSee('Ma réclamation visible')
            ->assertSee($mienne->reference)
            ->assertDontSee('Réclamation d\'un autre')
            ->assertSee($mienne->statutLibelle());
    }

    public function test_modification_et_suppression_par_le_proprietaire(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'À corriger');

        $this->actingAs($this->client)
            ->put(route('reclamations.update', $reclamation), [
                'produit_id' => $this->produit->id,
                'sujet' => 'Sujet corrigé',
                'description' => 'Description mise à jour.',
                'priorite' => 'faible',
            ])
            ->assertRedirect(route('reclamations.show', $reclamation));

        $this->assertSame('Sujet corrigé', $reclamation->fresh()->sujet);

        $this->actingAs($this->client)
            ->delete(route('reclamations.destroy', $reclamation))
            ->assertRedirect(route('reclamations.index'));

        $this->assertDatabaseMissing('reclamations', ['id' => $reclamation->id]);
    }

    public function test_une_reclamation_prise_en_charge_n_est_plus_modifiable(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'En cours', ['statut' => 'en_cours']);

        $this->actingAs($this->client)->get(route('reclamations.edit', $reclamation))->assertForbidden();
        $this->actingAs($this->client)->delete(route('reclamations.destroy', $reclamation))->assertForbidden();
    }

    public function test_un_autre_consommateur_ne_peut_pas_voir_la_reclamation(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Privée');

        $this->actingAs($this->autreClient)
            ->get(route('reclamations.show', $reclamation))
            ->assertForbidden();
    }

    public function test_le_producteur_voit_et_repond_aux_reclamations_sur_ses_produits(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Sur mon produit');

        $this->actingAs($this->producteur)
            ->get(route('acteur.reclamations.index'))
            ->assertOk()
            ->assertSee('Sur mon produit');

        $this->actingAs($this->producteur)
            ->post(route('reclamations.reponses.store', $reclamation), ['message' => 'Nous vérifions la parcelle.'])
            ->assertRedirect();

        $this->assertSame('en_cours', $reclamation->fresh()->statut);
    }

    public function test_ajout_d_une_reponse_et_changement_de_statut(): void
    {
        Notification::fake();
        $reclamation = $this->creerReclamation($this->client, 'Besoin d\'une réponse');

        $this->actingAs($this->admin)
            ->post(route('admin.reclamations.reponses.store', $reclamation), [
                'message' => 'Nous examinons votre dossier.',
            ])
            ->assertRedirect();

        $reclamation->refresh();
        $this->assertSame('en_cours', $reclamation->statut);
        $this->assertCount(1, $reclamation->reponses);
        $this->assertSame($this->admin->id, $reclamation->reponses->first()->user_id);

        $this->actingAs($this->admin)
            ->patch(route('admin.reclamations.statut', $reclamation), ['statut' => 'resolue'])
            ->assertRedirect(route('admin.reclamations.show', $reclamation));

        $reclamation->refresh();
        $this->assertSame('resolue', $reclamation->statut);
        $this->assertNotNull($reclamation->resolue_at);
        $this->assertTrue($reclamation->slaRespecte());
        Notification::assertSentTo($this->client, ReclamationNotification::class, fn ($n) => $n->evenement === 'statut');
    }

    public function test_un_dossier_cloture_n_accepte_plus_de_reponse(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Fermée', ['statut' => 'resolue']);

        $this->actingAs($this->client)
            ->post(route('reclamations.reponses.store', $reclamation), ['message' => 'Encore un mot'])
            ->assertForbidden();
    }

    public function test_le_client_cloture_puis_evalue_le_traitement(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'À clôturer', ['statut' => 'en_cours']);

        $this->actingAs($this->client)->patch(route('reclamations.cloturer', $reclamation))->assertRedirect();
        $this->assertSame('resolue', $reclamation->fresh()->statut);

        $this->actingAs($this->client)
            ->post(route('reclamations.evaluer', $reclamation), ['satisfaction_note' => 4, 'satisfaction_commentaire' => 'Rapide et efficace'])
            ->assertRedirect();

        $this->assertSame(4, $reclamation->fresh()->satisfaction_note);

        // Une seule évaluation par dossier
        $this->actingAs($this->client)->post(route('reclamations.evaluer', $reclamation), ['satisfaction_note' => 1])->assertForbidden();
    }

    public function test_un_dossier_en_retard_peut_etre_relance(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Oubliée', ['priorite' => 'moyenne']);
        $reclamation->forceFill(['echeance_at' => now()->subHour()])->saveQuietly();

        $this->assertTrue($reclamation->fresh()->estEnRetard());
        $this->assertStringStartsWith('En retard', $reclamation->fresh()->slaLibelle());

        $this->actingAs($this->client)->post(route('reclamations.relancer', $reclamation))->assertRedirect();

        $this->assertSame('haute', $reclamation->fresh()->priorite);
        $this->assertDatabaseHas('historique_reclamations', ['reclamation_id' => $reclamation->id, 'type' => 'escalade']);
    }

    public function test_l_administrateur_liste_filtre_et_supprime(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Filtre urgente', [
            'priorite' => 'urgente',
            'statut' => 'en_attente',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reclamations.index', ['q' => 'Filtre urgente', 'statut' => 'en_attente', 'priorite' => 'urgente']))
            ->assertOk()
            ->assertSee('Filtre urgente')
            ->assertSee($this->client->name)
            ->assertSee($this->produit->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.reclamations.destroy', $reclamation))
            ->assertRedirect(route('admin.reclamations.index'));

        $this->assertDatabaseMissing('reclamations', ['id' => $reclamation->id]);
    }

    public function test_le_back_office_est_interdit_aux_clients(): void
    {
        $this->actingAs($this->client)->get(route('admin.reclamations.index'))->assertForbidden();
        $this->actingAs($this->producteur)->get(route('admin.reclamations.index'))->assertForbidden();
    }

    public function test_le_copilote_redige_un_brouillon_sans_ia(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Colis en retard', ['categorie' => 'livraison']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.reclamations.copilote', $reclamation), ['ton' => 'formel'])
            ->assertOk()
            ->assertJsonPath('source', 'Modèle local')
            ->assertJson(fn ($json) => $json->where('message', fn ($m) => str_contains($m, 'Madame, Monsieur'))->etc());
    }

    public function test_le_dossier_pdf_est_telechargeable(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Pour le PDF');

        $this->actingAs($this->admin)
            ->get(route('admin.reclamations.pdf', $reclamation))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_les_relations_eloquent(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Relations');
        $reponse = ReponseReclamation::query()->create([
            'reclamation_id' => $reclamation->id,
            'user_id' => $this->admin->id,
            'message' => 'Réponse de test',
        ]);

        $chargee = Reclamation::query()->with(['user', 'produit', 'reponses.user'])->find($reclamation->id);

        $this->assertTrue($chargee->user->is($this->client));
        $this->assertTrue($chargee->produit->is($this->produit));
        $this->assertTrue($chargee->reponses->first()->is($reponse));
        $this->assertTrue($this->produit->reclamations->contains($reclamation));
        $this->assertTrue($this->client->reclamations->contains($reclamation));
    }

    public function test_le_service_d_analyse_classe_le_sentiment(): void
    {
        $service = app(AiReclamationService::class);

        $urgent = $service->analyserReclamation('Danger', 'Produit toxique, je suis malade.');
        $this->assertSame('negatif', $urgent['sentiment']);
        $this->assertSame('urgente', $urgent['priorite']);
        $this->assertSame('securite_alimentaire', $urgent['categorie']);
        $this->assertSame('regles', $urgent['source']);

        $positif = $service->analyserReclamation('Merci', 'Produit excellent, je suis satisfait.');
        $this->assertSame('positif', $positif['sentiment']);
        $this->assertSame('faible', $positif['priorite']);

        $arabe = $service->analyserReclamation('مشكلة', 'البرتقال وصل فاسد');
        $this->assertSame('ar', $arabe['langue']);
    }
}
