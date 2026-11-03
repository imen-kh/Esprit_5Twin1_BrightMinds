<?php

namespace Tests\Feature\Tracabilite;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use App\Services\TracabiliteIAService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * IA du module Traçabilité : l'API Gemini est simulée (Http::fake), aucun appel réseau réel.
 * On vérifie le fonctionnement normal ET le repli sans IA quand l'API échoue.
 */
class TracabiliteIATest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    private Lot $lot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
            'services.anthropic.key' => null,
        ]);

        $this->producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $this->lot = Lot::factory()->create([
            'code' => 'LOT-2026-0300',
            'produit_id' => Product::factory()->create(['user_id' => $this->producteur->id])->id,
            'date_production' => now()->subDays(5)->toDateString(),
            'statut' => 'disponible',
        ]);

        // Transport à 14 °C : anomalie déterministe de chaîne du froid
        EtapeTracabilite::factory()->type('production')->a('Nabeul')->create([
            'lot_id' => $this->lot->id, 'user_id' => $this->producteur->id, 'date_etape' => now()->subDays(5)->setTime(7, 0), 'temperature' => 18,
        ]);
        EtapeTracabilite::factory()->type('transport')->a('Grombalia')->create([
            'lot_id' => $this->lot->id, 'date_etape' => now()->subDays(4), 'temperature' => 14,
        ]);
    }

    /**
     * Réponse Gemini (generateContent) contenant le texte donné.
     */
    private function reponseGemini(string $texte): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $texte]]]]]];
    }

    public function test_l_histoire_est_generee_puis_servie_depuis_le_cache(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini('Tout commence à Nabeul…'))]);

        $this->get(route('tracabilite.show', $this->lot))
            ->assertOk()
            ->assertSee("L'histoire de votre produit", false)
            ->assertSee('Tout commence à Nabeul…');

        $this->assertSame('Tout commence à Nabeul…', $this->lot->fresh()->histoire_ia);

        // Deuxième visite : histoire en cache, aucun nouvel appel à l'API
        $this->get(route('tracabilite.show', $this->lot))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_si_gemini_echoue_la_page_publique_s_affiche_sans_histoire(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'panne'], 500)]);

        $this->get(route('tracabilite.show', $this->lot))
            ->assertOk()
            ->assertDontSee("L'histoire de votre produit", false)
            // Le contrôle déterministe reste affiché, sans IA
            ->assertSee('Rupture de la chaîne du froid');

        $this->assertNull($this->lot->fresh()->histoire_ia);
    }

    public function test_l_analyse_ia_lit_le_json_meme_entoure_de_balises_markdown(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini(
            "```json\n{\"niveau_risque\": \"eleve\", \"resume\": \"Chaîne du froid rompue.\", \"recommandations\": [\"Contrôler le camion\"]}\n```"
        ))]);

        $analyse = app(TracabiliteIAService::class)->analyserAnomalies($this->lot, $this->lot->anomalies());

        $this->assertSame('élevé', $analyse['niveau_risque']);
        $this->assertSame('Chaîne du froid rompue.', $analyse['resume']);
        $this->assertSame(['Contrôler le camion'], $analyse['recommandations']);
    }

    public function test_bouton_analyser_avec_gemini_disponible(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini(
            '{"niveau_risque": "moyen", "resume": "Résumé IA.", "recommandations": ["Reco A"]}'
        ))]);

        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.analyser', $this->lot))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->producteur)->get(route('gestion.lots.show', $this->lot))
            ->assertOk()
            ->assertSee('Risque moyen')
            ->assertSee('Résumé IA.');
    }

    public function test_bouton_analyser_quand_gemini_echoue_seules_les_anomalies_deterministes_restent(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('Erreur interne', 500)]);

        $this->actingAs($this->producteur)
            ->post(route('gestion.lots.analyser', $this->lot))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($this->producteur)->get(route('gestion.lots.show', $this->lot))
            ->assertOk()
            ->assertDontSee('Généré par IA')
            ->assertSee('Rupture de la chaîne du froid');
    }

    public function test_anthropic_prend_le_relais_quand_gemini_est_limite(): void
    {
        config([
            'services.anthropic.key' => 'cle-anthropic-test',
            'services.anthropic.model' => 'claude-test',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Réponse de secours Claude.']],
            ]),
        ]);

        $this->postJson(route('tracabilite.assistant', $this->lot), ['question' => 'Quelle est la température du transport ?'])
            ->assertOk()
            ->assertJson(['reponse' => 'Réponse de secours Claude.', 'ia' => true]);
    }

    public function test_l_assistant_repond_a_partir_des_donnees_du_lot(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reponseGemini('Non, 14 °C pendant le transport.'))]);

        $this->postJson(route('tracabilite.assistant', $this->lot), ['question' => 'La chaîne du froid a-t-elle été respectée ?'])
            ->assertOk()
            ->assertJson(['reponse' => 'Non, 14 °C pendant le transport.', 'ia' => true]);

        // Les données du lot sont envoyées à Gemini avec la consigne stricte
        Http::assertSent(fn ($requete) => str_contains($requete['contents'][0]['parts'][0]['text'], 'Je n\'ai pas cette information')
            && str_contains($requete['contents'][0]['parts'][0]['text'], 'LOT-2026-0300'));
    }

    public function test_l_assistant_signale_l_indisponibilite_si_gemini_echoue(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('Erreur interne', 500)]);

        $this->postJson(route('tracabilite.assistant', $this->lot), ['question' => 'D\'où vient ce produit ?'])
            ->assertStatus(503)
            ->assertJson(['ia' => false]);
    }

    public function test_sans_cle_gemini_aucun_appel_n_est_fait(): void
    {
        config(['services.gemini.key' => null, 'services.anthropic.key' => null]);
        Http::fake();

        $this->get(route('tracabilite.show', $this->lot))->assertOk();
        $this->assertNull(app(TracabiliteIAService::class)->genererHistoire($this->lot));

        Http::assertNothingSent();
    }
}
