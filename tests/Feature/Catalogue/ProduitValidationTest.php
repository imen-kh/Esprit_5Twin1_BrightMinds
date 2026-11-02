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
 * Validation des produits entre producteur et administrateur.
 * Gemini est simulé : aucun appel réseau réel.
 */
class ProduitValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $producteur;

    private User $admin;

    private Category $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->producteur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->categorie = Category::query()->create([
            'name' => 'Huiles',
            'description' => 'Huiles alimentaires.',
            'status' => Category::VALIDATED,
            'origin' => Category::ORIGIN_ADMIN,
        ]);
    }

    public function test_un_produit_ajoute_reste_en_attente_et_hors_catalogue(): void
    {
        $this->simulerImage();

        $this->actingAs($this->producteur)
            ->post(route('acteur.produits.store'), $this->fiche())
            ->assertRedirect(route('acteur.produits.index'));

        $produit = Product::query()->where('name', 'Huile d olive')->firstOrFail();

        $this->assertSame(Product::PENDING, $produit->status);
        $this->get(route('produits'))->assertOk()->assertDontSee('Huile d olive');
        $this->get(route('produit.detail', $produit))->assertNotFound();
        $this->actingAs($this->producteur)
            ->get(route('acteur.produits.index'))
            ->assertOk()
            ->assertSee('En attente de validation');
    }

    public function test_l_admin_publie_seulement_en_approuvant(): void
    {
        $produit = $this->produitEnAttente();

        $this->actingAs($this->producteur)
            ->patch(route('admin.produits.approuver', $produit))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('admin.produits.statut', $produit))
            ->assertRedirect(route('admin.produits.revue', $produit));

        $this->assertSame(Product::PENDING, $produit->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.produits.approuver', $produit))
            ->assertRedirect(route('admin.produits.validation'));

        $this->assertSame(Product::PUBLISHED, $produit->fresh()->status);
        $this->get(route('produits'))->assertOk()->assertSee('Huile d olive');
    }

    public function test_le_refus_et_la_demande_de_modification_exigent_un_motif(): void
    {
        $produit = $this->produitEnAttente();

        $this->actingAs($this->admin)
            ->patch(route('admin.produits.refuser', $produit), ['motif' => 'court'])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Product::PENDING, $produit->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.produits.refuser', $produit), [
                'motif' => 'La photo ne correspond pas au produit annoncé.',
            ])
            ->assertRedirect(route('admin.produits.validation'));

        $produit->refresh();
        $this->assertSame(Product::REJECTED, $produit->status);

        $this->actingAs($this->producteur)
            ->get(route('acteur.produits.index'))
            ->assertSee('La photo ne correspond pas au produit annoncé.');

        $autre = $this->produitEnAttente('Tomates du Sahel');

        $this->actingAs($this->admin)
            ->patch(route('admin.produits.modification', $autre), [
                'motif' => 'Précisez le calibre et la période de récolte.',
            ]);

        $this->assertSame(Product::CHANGES, $autre->fresh()->status);
        $this->assertNotSame(Product::PUBLISHED, $autre->fresh()->status);
    }

    public function test_une_modification_du_producteur_renvoie_le_produit_en_validation(): void
    {
        $this->simulerImage();
        $produit = $this->produitEnAttente();
        $produit->update([
            'status' => Product::REJECTED,
            'review_note' => 'Photo trop sombre pour identifier le produit.',
            'image' => 'https://res.cloudinary.com/demo/image/upload/v1/nutritrace/products/test.jpg',
        ]);

        $this->actingAs($this->producteur)
            ->put(route('acteur.produits.update', $produit), $this->fiche([
                'name' => 'Huile d olive claire',
                'image' => $this->fichierImage('huile-2.jpg'),
            ]))
            ->assertRedirect(route('acteur.produits.index'));

        $produit->refresh();
        $this->assertSame(Product::PENDING, $produit->status);
        $this->assertNull($produit->review_note);
    }

    public function test_l_analyse_ia_ne_publie_jamais_le_produit(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => '{"resume":"Origine à préciser.","coherent":false,"anomalies":["Origine vague"],"corrections":["Indiquer la ville"],"publier":true}',
                    ]]],
                ]],
            ]),
        ]);

        $produit = $this->produitEnAttente();
        Product::factory()->create([
            'name' => 'Huile d olive',
            'status' => Product::PUBLISHED,
            'user_id' => User::factory()->create(['role' => 'acteur', 'type_acteur' => 'producteur'])->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.produits.revue', $produit))
            ->assertOk()
            ->assertDontSee('Aucun produit similaire');

        $this->actingAs($this->admin)
            ->post(route('admin.produits.analyser', $produit))
            ->assertRedirect();

        $produit->refresh();
        $this->assertSame(Product::PENDING, $produit->status);
        $this->assertSame('Origine à préciser.', $produit->ai_analysis['resume']);
        $this->assertFalse($produit->ai_analysis['coherent']);
    }

    public function test_sans_cle_gemini_l_analyse_n_appelle_pas_l_api(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $produit = $this->produitEnAttente();

        $this->actingAs($this->admin)
            ->post(route('admin.produits.analyser', $produit))
            ->assertRedirect()
            ->assertSessionHas('warning');

        Http::assertNothingSent();
        $this->assertSame(Product::PENDING, $produit->fresh()->status);
        $this->assertNull($produit->fresh()->ai_analysis);
    }

    public function test_la_photo_propose_une_fiche_et_l_echec_laisse_le_formulaire_utilisable(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.secours' => [],
        ]);

        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => '{"nom":"Huile d olive","description":"Huile extraite d olives tunisiennes.","categorie_id":'.$this->categorie->id.',"ingredients":"","origine":"Sfax"}',
                    ]]],
                ]],
            ])
            ->push('panne', 500);

        $this->actingAs($this->producteur)
            ->post(route('acteur.produits.generer-ia'), [
                'image' => $this->fichierImage('huile.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Huile d olive')
            ->assertJsonPath('category_id', $this->categorie->id);

        $this->assertSame(0, Product::query()->count());

        $this->actingAs($this->producteur)
            ->postJson(route('acteur.produits.generer-ia'), [
                'image' => $this->fichierImage('huile.jpg'),
            ])
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'L’analyse de l’image est indisponible. Le formulaire reste utilisable.']);
    }

    public function test_un_distributeur_ne_peut_pas_generer_une_fiche(): void
    {
        $distributeur = User::factory()->create(['role' => 'acteur', 'type_acteur' => 'distributeur']);

        $this->actingAs($distributeur)
            ->postJson(route('acteur.produits.generer-ia'), [
                'image' => $this->fichierImage('huile.jpg'),
            ])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $remplacement
     * @return array<string, mixed>
     */
    private function fiche(array $remplacement = []): array
    {
        return array_merge([
            'name' => 'Huile d olive',
            'description' => 'Huile d olive extra vierge du Sahel tunisien.',
            'price' => '18.500',
            'origin' => 'Sfax, Tunisie',
            'category_id' => $this->categorie->id,
            'image' => $this->fichierImage('huile.jpg'),
        ], $remplacement);
    }

    private function produitEnAttente(string $nom = 'Huile d olive'): Product
    {
        return Product::factory()->create([
            'name' => $nom,
            'description' => 'Huile d olive extra vierge du Sahel tunisien.',
            'origin' => 'Sfax',
            'status' => Product::PENDING,
            'category_id' => $this->categorie->id,
            'user_id' => $this->producteur->id,
        ]);
    }

    private function fichierImage(string $nom): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'nt');
        file_put_contents($chemin, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABALDA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBkeFxlZ2P/2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2P/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCfAAH/2Q=='
        ));

        return new UploadedFile($chemin, $nom, 'image/jpeg', null, true);
    }

    private function simulerImage(): void
    {
        $this->mock(ProductImageService::class, function ($mock) {
            $mock->shouldReceive('envoyer')->andReturn('https://res.cloudinary.com/demo/image/upload/v1/nutritrace/products/test.jpg');
            $mock->shouldReceive('supprimer')->andReturnNull();
        });
    }
}
