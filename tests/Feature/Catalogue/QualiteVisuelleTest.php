<?php

namespace Tests\Feature\Catalogue;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Qualité visuelle indicative. Gemini est simulé, la génération de fiche n'est pas appelée.
 */
class QualiteVisuelleTest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    private User $admin;

    private Category $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);

        $this->producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->categorie = Category::query()->create([
            'name' => 'Légumes',
            'description' => 'Légumes frais de saison.',
            'status' => Category::VALIDATED,
            'origin' => Category::ORIGIN_ADMIN,
        ]);
    }

    public function test_le_score_est_indicatif_et_enregistre_avec_la_photo_sans_publier(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini(
                '{"evaluable":true,"score":40,"classification":"Excellente","defauts":["Tache brune visible sur le côté"],"recommandations":["Écarter ce fruit du premier choix"]}'
            )),
        ]);

        $this->actingAs($this->producteur)
            ->get(route('acteur.produits.create'))
            ->assertOk()
            ->assertSee('Générer avec IA')
            ->assertSee('Analyser la qualité avec IA');

        $this->actingAs($this->producteur)
            ->postJson(route('acteur.produits.qualite-ia'), ['image' => $this->fichierImage('tomate.jpg')])
            ->assertOk()
            ->assertJsonPath('evaluable', true)
            ->assertJsonPath('score', 40)
            ->assertJsonPath('classification', 'Faible')
            ->assertJsonPath('defauts.0', 'Tache brune visible sur le côté');

        $this->assertSame(0, Product::query()->count());

        $this->mock(ProductImageService::class, function ($mock) {
            $mock->shouldReceive('envoyer')->andReturn('https://res.cloudinary.com/demo/image/upload/v1/nutritrace/products/tomate.jpg');
            $mock->shouldReceive('supprimer')->andReturnNull();
        });

        $this->actingAs($this->producteur)
            ->post(route('acteur.produits.store'), [
                'name' => 'Tomate du Sahel',
                'description' => 'Tomates de plein champ récoltées le matin.',
                'price' => '1.600',
                'origin' => 'Sousse, Tunisie',
                'category_id' => $this->categorie->id,
                'image' => $this->fichierImage('tomate.jpg'),
            ])
            ->assertRedirect(route('acteur.produits.index'));

        $produit = Product::query()->firstOrFail();
        $this->assertSame(Product::PENDING, $produit->status);
        $this->assertSame(40, $produit->quality_analysis['score']);
        $this->assertSame('Faible', $produit->quality_analysis['classification']);
        $this->assertStringContainsString('sécurité alimentaire', $produit->quality_analysis['avertissement']);

        $this->actingAs($this->admin)
            ->get(route('admin.produits.revue', $produit))
            ->assertOk()
            ->assertSee('40')
            ->assertSee('Faible')
            ->assertSee('Tache brune visible sur le côté')
            ->assertSee('ne garantit ni la fraîcheur');
    }

    public function test_une_photo_insuffisante_ne_fabrique_ni_score_ni_defaut(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini(
                '{"evaluable":false,"score":90,"defauts":["Moisissure inventée"],"recommandations":["Rapprocher le produit"]}'
            )),
        ]);

        $this->actingAs($this->producteur)
            ->postJson(route('acteur.produits.qualite-ia'), ['image' => $this->fichierImage('floue.jpg')])
            ->assertOk()
            ->assertJsonPath('evaluable', false)
            ->assertJsonPath('score', null)
            ->assertJsonPath('classification', null)
            ->assertJsonPath('defauts', [])
            ->assertJsonPath('recommandations.0', 'Rapprocher le produit');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_sans_cle_ou_si_gemini_echoue_le_formulaire_reste_utilisable(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->actingAs($this->producteur)
            ->postJson(route('acteur.produits.qualite-ia'), ['image' => $this->fichierImage('pomme.jpg')])
            ->assertStatus(503);

        Http::assertNothingSent();

        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('panne', 500),
        ]);

        $this->actingAs($this->producteur)
            ->postJson(route('acteur.produits.qualite-ia'), ['image' => $this->fichierImage('pomme.jpg')])
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'L’analyse de qualité est indisponible. Le formulaire reste utilisable.']);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_un_distributeur_ne_peut_pas_lancer_l_analyse(): void
    {
        $distributeur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'distributeur']);

        $this->actingAs($distributeur)
            ->postJson(route('acteur.produits.qualite-ia'), ['image' => $this->fichierImage('fraise.jpg')])
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function gemini(string $json): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]]];
    }

    private function fichierImage(string $nom): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'nt');
        file_put_contents($chemin, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABALDA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBkeFxlZ2P/2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2P/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCfAAH/2Q=='
        ));

        return new UploadedFile($chemin, $nom, 'image/jpeg', null, true);
    }
}
