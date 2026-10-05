<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Models\User;
use App\Services\AiReclamationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReclamationTest extends TestCase
{
    use DatabaseTransactions;

    private User $consommateur;

    private User $admin;

    private User $autre;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consommateur = User::factory()->create(['role' => 'consommateur']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->autre = User::factory()->create(['role' => 'consommateur']);

        $category = Category::query()->create([
            'name' => 'Cat '.uniqid(),
            'description' => 'Catégorie de test',
            'status' => Category::VALIDATED,
        ]);

        $this->product = Product::query()->create([
            'name' => 'Produit réclamation '.uniqid(),
            'description' => 'Produit de test',
            'price' => 4.200,
            'status' => 'published',
            'category_id' => $category->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get(route('reclamations.index'))->assertRedirect(route('login'));
    }

    public function test_un_consommateur_cree_une_reclamation(): void
    {
        $this->actingAs($this->consommateur)
            ->post(route('reclamations.store'), [
                'produit_id' => $this->product->id,
                'commande_id' => 2048,
                'sujet' => 'Colis abîmé',
                'description' => 'Le carton est cassé et le produit est pourri. Je demande un remboursement.',
                'priorite' => 'haute',
            ])
            ->assertRedirect();

        $reclamation = Reclamation::query()->where('sujet', 'Colis abîmé')->first();

        $this->assertNotNull($reclamation);
        $this->assertSame($this->consommateur->id, $reclamation->user_id);
        $this->assertSame($this->product->id, $reclamation->produit_id);
        $this->assertSame(2048, $reclamation->commande_id);
        $this->assertSame('en_attente', $reclamation->statut);
        $this->assertSame('negatif', $reclamation->sentiment);
        $this->assertNotEmpty($reclamation->suggestion_reponse);
        $this->assertTrue($reclamation->produit->is($this->product));
        $this->assertTrue($reclamation->user->is($this->consommateur));
    }

    public function test_la_validation_refuse_une_reclamation_incomplete(): void
    {
        $this->actingAs($this->consommateur)
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

    public function test_la_liste_affiche_les_reclamation_du_consommateur(): void
    {
        $mienne = $this->creerReclamation($this->consommateur, 'Ma réclamation visible');
        $this->creerReclamation($this->autre, 'Réclamation d\'un autre');

        $this->actingAs($this->consommateur)
            ->get(route('reclamations.index'))
            ->assertOk()
            ->assertSee('Ma réclamation visible')
            ->assertDontSee('Réclamation d\'un autre')
            ->assertSee($mienne->statutLibelle());
    }

    public function test_modification_et_suppression_par_le_proprietaire(): void
    {
        $reclamation = $this->creerReclamation($this->consommateur, 'À corriger');

        $this->actingAs($this->consommateur)
            ->put(route('reclamations.update', $reclamation), [
                'produit_id' => $this->product->id,
                'sujet' => 'Sujet corrigé',
                'description' => 'Description mise à jour.',
                'priorite' => 'faible',
            ])
            ->assertRedirect(route('reclamations.show', $reclamation));

        $this->assertSame('Sujet corrigé', $reclamation->fresh()->sujet);

        $this->actingAs($this->consommateur)
            ->delete(route('reclamations.destroy', $reclamation))
            ->assertRedirect(route('reclamations.index'));

        $this->assertDatabaseMissing('reclamations', ['id' => $reclamation->id]);
    }

    public function test_un_autre_consommateur_ne_peut_pas_voir_la_reclamation(): void
    {
        $reclamation = $this->creerReclamation($this->consommateur, 'Privée');

        $this->actingAs($this->autre)
            ->get(route('reclamations.show', $reclamation))
            ->assertForbidden();
    }

    public function test_ajout_d_une_reponse_et_changement_de_statut(): void
    {
        $reclamation = $this->creerReclamation($this->consommateur, 'Besoin d\'une réponse');

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

        $this->assertSame('resolue', $reclamation->fresh()->statut);
    }

    public function test_l_administrateur_liste_filtre_et_supprime(): void
    {
        $reclamation = $this->creerReclamation($this->consommateur, 'Filtre urgente', [
            'priorite' => 'urgente',
            'statut' => 'en_attente',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reclamations.index', ['q' => 'Filtre urgente', 'statut' => 'en_attente', 'priorite' => 'urgente']))
            ->assertOk()
            ->assertSee('Filtre urgente')
            ->assertSee($this->consommateur->name)
            ->assertSee($this->product->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.reclamations.destroy', $reclamation))
            ->assertRedirect(route('admin.reclamations.index'));

        $this->assertDatabaseMissing('reclamations', ['id' => $reclamation->id]);
    }

    public function test_les_relations_eloquent(): void
    {
        $reclamation = $this->creerReclamation($this->consommateur, 'Relations');
        $reponse = ReponseReclamation::query()->create([
            'reclamation_id' => $reclamation->id,
            'user_id' => $this->admin->id,
            'message' => 'Réponse de test',
        ]);

        $chargee = Reclamation::query()->with(['user', 'produit', 'reponses.user'])->find($reclamation->id);

        $this->assertTrue($chargee->user->is($this->consommateur));
        $this->assertTrue($chargee->produit->is($this->product));
        $this->assertTrue($chargee->reponses->first()->is($reponse));
        $this->assertTrue($this->product->reclamations->contains($reclamation));
        $this->assertTrue($this->consommateur->reclamations->contains($reclamation));
    }

    public function test_le_service_d_analyse_classe_le_sentiment(): void
    {
        $service = app(AiReclamationService::class);

        $urgent = $service->analyserReclamation('Danger', 'Produit toxique, je suis malade.');
        $this->assertSame('negatif', $urgent['sentiment']);
        $this->assertSame('urgente', $urgent['priorite']);

        $positif = $service->analyserReclamation('Merci', 'Produit excellent, je suis satisfait.');
        $this->assertSame('positif', $positif['sentiment']);
        $this->assertSame('faible', $positif['priorite']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function creerReclamation(User $user, string $sujet, array $overrides = []): Reclamation
    {
        return Reclamation::query()->create(array_merge([
            'user_id' => $user->id,
            'produit_id' => $this->product->id,
            'sujet' => $sujet,
            'description' => 'Description de test.',
            'statut' => 'en_attente',
            'priorite' => 'moyenne',
        ], $overrides));
    }
}
