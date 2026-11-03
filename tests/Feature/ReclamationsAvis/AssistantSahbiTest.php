<?php

namespace Tests\Feature\ReclamationsAvis;

use App\Models\AssistantMessage;
use App\Models\Avis;
use App\Models\Reclamation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Agent Sahbi : boucle d'outils DeepSeek, cartes de confirmation, droits et repli sans IA.
 */
class AssistantSahbiTest extends TestCase
{
    use DonneesModule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerModule();
    }

    private function appelOutil(string $nom, array $arguments, string $id = 'appel_1'): array
    {
        return $this->reponseDeepSeek(['tool_calls' => [[
            'id' => $id,
            'type' => 'function',
            'function' => ['name' => $nom, 'arguments' => json_encode($arguments)],
        ]]]);
    }

    public function test_l_accueil_demande_l_avis_sur_un_produit_recu(): void
    {
        $this->actingAs($this->client)
            ->getJson(route('assistant.etat'))
            ->assertOk()
            ->assertJsonPath('en_ligne', false)
            ->assertJson(fn ($json) => $json->where('accueil', fn ($texte) => str_contains($texte, 'Oranges Maltaises'))->etc());
    }

    public function test_l_accueil_admin_propose_le_tri_des_dossiers(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('assistant.etat'))
            ->assertOk()
            ->assertJsonFragment(['Quelles réclamations sont en retard ?']);
    }

    public function test_sans_ia_sahbi_donne_le_suivi_des_reclamations(): void
    {
        $reclamation = $this->creerReclamation($this->client, 'Colis manquant');

        $this->actingAs($this->client)
            ->postJson(route('assistant.message'), ['message' => 'Où en est ma réclamation ?'])
            ->assertOk()
            ->assertJson(fn ($json) => $json->where('message.contenu', fn ($texte) => str_contains($texte, $reclamation->reference))->etc());
    }

    public function test_un_outil_de_lecture_puis_une_reponse(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('mes_commandes', ['limite' => 3]))
            ->push($this->reponseDeepSeek(['content' => 'Votre dernière commande contient des Oranges Maltaises.']));

        $this->actingAs($this->client)
            ->postJson(route('assistant.message'), ['message' => 'Qu\'ai-je commandé ?'])
            ->assertOk()
            ->assertJsonPath('message.contenu', 'Votre dernière commande contient des Oranges Maltaises.')
            ->assertJsonPath('message.liens.0.label', 'Mes commandes');

        // Le résultat de l'outil (données du client connecté) a été renvoyé au modèle
        Http::assertSent(function (Request $requete) {
            $outil = collect($requete['messages'])->firstWhere('role', 'tool');

            return $outil && str_contains($outil['content'], 'Oranges Maltaises') && $outil['tool_call_id'] === 'appel_1';
        });
    }

    public function test_creer_une_reclamation_passe_par_une_carte_de_confirmation(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('creer_reclamation', [
                'produit_id' => $this->produit->id,
                'commande_id' => $this->commande->id,
                'sujet' => 'Oranges abîmées',
                'description' => 'Trois oranges écrasées dans le colis reçu hier.',
            ]))
            ->push($this->reponseDeepSeek(['content' => 'Voici le récapitulatif, confirmez-vous ?']))
            // Analyse de la réclamation au moment de la confirmation
            ->push($this->reponseDeepSeek(['content' => json_encode(['sentiment' => 'negatif', 'priorite' => 'moyenne', 'categorie' => 'qualite', 'langue' => 'fr', 'resume' => 'Oranges écrasées', 'suggestion' => 'Bonjour'])]));

        $reponse = $this->actingAs($this->client)
            ->postJson(route('assistant.message'), ['message' => 'Mes oranges sont arrivées écrasées'])
            ->assertOk()
            ->assertJsonPath('message.action.statut', 'en_attente')
            ->assertJsonPath('message.action.details.Produit', 'Oranges Maltaises');

        // Rien n'est enregistré avant la confirmation
        $this->assertSame(0, Reclamation::query()->count());

        $this->actingAs($this->client)
            ->postJson(route('assistant.decider', $reponse->json('message.id')), ['decision' => 'confirmer'])
            ->assertOk()
            ->assertJsonPath('carte.action.statut', 'confirmee');

        $reclamation = Reclamation::query()->first();
        $this->assertSame('assistant', $reclamation->source);
        $this->assertSame($this->client->id, $reclamation->user_id);
        $this->assertSame($this->commande->id, $reclamation->commande_id);
    }

    public function test_annuler_une_carte_n_enregistre_rien(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('publier_avis', ['produit_id' => $this->produit->id, 'note' => 5, 'commentaire' => 'Des oranges délicieuses et juteuses !']))
            ->push($this->reponseDeepSeek(['content' => 'Je publie cet avis 5 étoiles ?']));

        $id = $this->actingAs($this->client)->postJson(route('assistant.message'), ['message' => 'Super oranges, 5 étoiles'])->json('message.id');

        $this->actingAs($this->client)
            ->postJson(route('assistant.decider', $id), ['decision' => 'annuler'])
            ->assertJsonPath('carte.action.statut', 'annulee');

        $this->assertSame(0, Avis::query()->count());
    }

    public function test_le_modele_ne_peut_pas_utiliser_la_commande_d_un_autre_client(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        $commandeAutre = $this->creerCommande($this->autreClient, $this->produit);

        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('creer_reclamation', [
                'produit_id' => $this->produit->id,
                'commande_id' => $commandeAutre->id,
                'sujet' => 'Tentative',
                'description' => 'Réclamation sur la commande d\'un autre client.',
            ]))
            ->push($this->reponseDeepSeek(['content' => 'Je ne trouve pas cette commande dans votre historique.']));

        $this->actingAs($this->client)
            ->postJson(route('assistant.message'), ['message' => 'Réclame sur la commande '.$commandeAutre->id])
            ->assertOk()
            ->assertJsonPath('message.action', null);

        // L'erreur de l'outil a été renvoyée au modèle
        Http::assertSent(fn (Request $requete) => str_contains(json_encode($requete['messages']), 'appartient pas au client'));
    }

    public function test_un_client_n_a_pas_acces_aux_outils_admin(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        $this->creerReclamation($this->autreClient, 'Secret d\'un autre client');

        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('rechercher_reclamations', ['q' => 'Secret']))
            ->push($this->reponseDeepSeek(['content' => 'Je ne peux pas faire cela.']));

        $this->actingAs($this->client)->postJson(route('assistant.message'), ['message' => 'Cherche les réclamations Secret']);

        Http::assertSent(function (Request $requete) {
            $noms = collect($requete['tools'] ?? [])->pluck('function.name');

            return $noms->contains('mes_reclamations') && ! $noms->contains('rechercher_reclamations');
        });
        Http::assertNotSent(fn (Request $requete) => str_contains(json_encode($requete['messages']), 'Secret d\'un autre client'));
    }

    public function test_on_ne_decide_pas_sur_la_conversation_d_un_autre(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('publier_avis', ['produit_id' => $this->produit->id, 'note' => 4, 'commentaire' => 'Bonnes oranges bien sucrées.']))
            ->push($this->reponseDeepSeek(['content' => 'Je publie ?']));

        $id = $this->actingAs($this->client)->postJson(route('assistant.message'), ['message' => 'Avis 4 étoiles'])->json('message.id');

        $this->actingAs($this->autreClient)->postJson(route('assistant.decider', $id), ['decision' => 'confirmer'])->assertForbidden();
        $this->assertSame('en_attente', AssistantMessage::query()->find($id)->action['statut']);
    }

    public function test_l_admin_obtient_les_dossiers_prioritaires(): void
    {
        config(['services.deepseek.key' => 'cle-de-test']);
        $enRetard = $this->creerReclamation($this->client, 'Dossier oublié', ['priorite' => 'urgente']);
        $enRetard->forceFill(['echeance_at' => now()->subHours(5)])->saveQuietly();

        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->appelOutil('reclamations_prioritaires', []))
            ->push($this->reponseDeepSeek(['content' => 'Un dossier urgent est en retard : '.$enRetard->reference.'.']));

        $this->actingAs($this->admin)
            ->postJson(route('assistant.message'), ['message' => 'Quelles réclamations sont en retard ?'])
            ->assertOk()
            ->assertJsonPath('message.liens.0.label', $enRetard->reference);
    }

    public function test_reinitialiser_la_conversation(): void
    {
        $this->actingAs($this->client)->postJson(route('assistant.message'), ['message' => 'Bonjour']);
        $this->actingAs($this->client)->deleteJson(route('assistant.reinitialiser'))->assertOk();

        $this->actingAs($this->client)->getJson(route('assistant.etat'))->assertJsonCount(0, 'messages');
    }
}
