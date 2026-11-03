<?php

namespace Tests\Feature\ReclamationsAvis;

use App\Models\Avis;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisTest extends TestCase
{
    use DonneesModule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerModule();
    }

    public function test_un_client_publie_un_avis_achat_verifie(): void
    {
        $this->actingAs($this->client)
            ->post(route('avis.store', $this->produit), [
                'note' => 5,
                'titre' => 'Délicieuses',
                'commentaire' => 'Des oranges très fraîches et savoureuses, livraison rapide.',
            ])
            ->assertRedirect(route('produit.detail', $this->produit).'#avis');

        $avis = Avis::query()->first();
        $this->assertSame('publie', $avis->statut);
        $this->assertTrue($avis->achat_verifie);
        $this->assertSame('positif', $avis->sentiment);
        $this->assertArrayHasKey('fraicheur', $avis->aspects);
    }

    public function test_la_page_produit_affiche_moyenne_repartition_et_synthese(): void
    {
        Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id, 'note' => 5, 'commentaire' => 'Excellent goût, très frais.']);
        Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->autreClient->id, 'note' => 4, 'commentaire' => 'Bon produit, emballage correct.']);

        $this->actingAs($this->client)
            ->get(route('produit.detail', $this->produit))
            ->assertOk()
            ->assertSee('Avis clients')
            ->assertSee('4,5')
            ->assertSee('Ce que les clients en pensent')
            ->assertSee('Excellent goût, très frais.')
            ->assertSee('Modifier mon avis');
    }

    public function test_un_avis_injurieux_part_en_moderation(): void
    {
        $this->actingAs($this->client)->post(route('avis.store', $this->produit), [
            'note' => 1,
            'commentaire' => 'Produit de merde, vendeur escroc, appelez le 22 333 444.',
        ]);

        $avis = Avis::query()->first();
        $this->assertSame('en_moderation', $avis->statut);
        $this->assertNotNull($avis->moderation_raison);

        // Invisible sur la page publique tant qu'il n'est pas validé
        $this->actingAs($this->autreClient)->get(route('produit.detail', $this->produit))->assertDontSee('vendeur escroc');
    }

    public function test_un_seul_avis_par_produit_et_pas_sur_son_propre_produit(): void
    {
        $donnees = ['note' => 4, 'commentaire' => 'Très bon produit, je recommande vivement.'];

        $this->actingAs($this->client)->post(route('avis.store', $this->produit), $donnees)->assertRedirect();
        $this->actingAs($this->client)->post(route('avis.store', $this->produit), $donnees)->assertForbidden();
        $this->actingAs($this->producteur)->post(route('avis.store', $this->produit), $donnees)->assertForbidden();
        $this->actingAs($this->admin)->post(route('avis.store', $this->produit), $donnees)->assertForbidden();
    }

    public function test_la_validation_de_l_avis(): void
    {
        $this->actingAs($this->client)
            ->post(route('avis.store', $this->produit), ['note' => 7, 'commentaire' => 'court'])
            ->assertSessionHasErrors(['note', 'commentaire']);
    }

    public function test_modifier_et_supprimer_son_avis(): void
    {
        $avis = Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id, 'note' => 3]);

        $this->actingAs($this->autreClient)->get(route('avis.edit', $avis))->assertForbidden();

        $this->actingAs($this->client)
            ->put(route('avis.update', $avis), ['note' => 5, 'commentaire' => 'Finalement excellent, très sucré.'])
            ->assertRedirect();
        $this->assertSame(5, $avis->fresh()->note);

        $this->actingAs($this->client)->delete(route('avis.destroy', $avis))->assertRedirect();
        $this->assertDatabaseMissing('avis', ['id' => $avis->id]);
    }

    public function test_vote_utile_bascule_et_signalements_renvoient_en_moderation(): void
    {
        $avis = Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id]);

        $this->actingAs($this->autreClient)->post(route('avis.reagir', $avis), ['type' => 'utile']);
        $this->assertSame(1, $avis->fresh()->utile_count);
        $this->actingAs($this->autreClient)->post(route('avis.reagir', $avis), ['type' => 'utile']);
        $this->assertSame(0, $avis->fresh()->utile_count);

        // Pas de vote sur son propre avis
        $this->actingAs($this->client)->post(route('avis.reagir', $avis), ['type' => 'utile'])->assertForbidden();

        foreach (User::factory()->count(3)->create(['role' => 'consommateur']) as $user) {
            $this->actingAs($user)->post(route('avis.reagir', $avis), ['type' => 'signalement', 'motif' => 'spam']);
        }

        $this->assertSame('en_moderation', $avis->fresh()->statut);
        $this->assertSame(3, $avis->fresh()->signalements_count);
    }

    public function test_droit_de_reponse_du_producteur(): void
    {
        $avis = Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id, 'note' => 2]);
        $autreProducteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);

        $this->actingAs($autreProducteur)
            ->post(route('acteur.avis.repondre', $avis), ['reponse_producteur' => 'Ce n\'est pas mon produit.'])
            ->assertForbidden();

        $this->actingAs($this->producteur)
            ->post(route('acteur.avis.repondre', $avis), ['reponse_producteur' => 'Merci, nous avons changé de cagettes.'])
            ->assertRedirect();

        $this->assertSame('Merci, nous avons changé de cagettes.', $avis->fresh()->reponse_producteur);

        $this->actingAs($this->producteur)->get(route('acteur.avis.index'))->assertOk()->assertSee('Oranges Maltaises');
    }

    public function test_un_avis_negatif_devient_une_reclamation(): void
    {
        $this->actingAs($this->client)->post(route('avis.store', $this->produit), [
            'note' => 1,
            'titre' => 'Oranges pourries',
            'commentaire' => 'La moitié des oranges étaient pourries à la réception.',
        ])->assertSessionHas('avis_a_convertir');

        $avis = Avis::query()->first();
        $this->assertTrue($avis->meriteReclamation());

        $this->actingAs($this->client)->post(route('avis.convertir', $avis))->assertRedirect();

        $reclamation = Reclamation::query()->first();
        $this->assertSame('avis', $reclamation->source);
        $this->assertSame($avis->id, $reclamation->avis_id);
        $this->assertSame($this->commande->id, $reclamation->commande_id);
        $this->assertSame('Oranges pourries', $reclamation->sujet);

        // Conversion idempotente
        $this->actingAs($this->client)->post(route('avis.convertir', $avis));
        $this->assertSame(1, Reclamation::query()->count());
    }

    public function test_moderation_par_l_administrateur(): void
    {
        $avis = Avis::factory()->create(['produit_id' => $this->produit->id, 'user_id' => $this->client->id, 'statut' => 'en_moderation', 'moderation_raison' => 'Signalé']);

        $this->actingAs($this->client)->get(route('admin.avis.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.avis.index', ['statut' => 'en_moderation']))->assertOk()->assertSee('Signalé');
        $this->actingAs($this->admin)->patch(route('admin.avis.publier', $avis))->assertRedirect();
        $this->assertSame('publie', $avis->fresh()->statut);

        $this->actingAs($this->admin)->patch(route('admin.avis.masquer', $avis), ['raison' => 'Hors sujet'])->assertRedirect();
        $this->assertSame('masque', $avis->fresh()->statut);
    }

    public function test_mes_avis_liste_les_produits_a_noter(): void
    {
        $this->actingAs($this->client)
            ->get(route('avis.index'))
            ->assertOk()
            ->assertSee('Produits reçus à noter')
            ->assertSee('Oranges Maltaises');
    }
}
