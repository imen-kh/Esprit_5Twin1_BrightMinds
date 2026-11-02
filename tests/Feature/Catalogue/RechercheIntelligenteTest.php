<?php

namespace Tests\Feature\Catalogue;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RechercheIntelligenteTest extends TestCase
{
    use RefreshDatabase;

    private Category $legumes;

    private Category $fruits;

    protected function setUp(): void
    {
        parent::setUp();

        $this->legumes = Category::query()->create([
            'name' => 'Légumes',
            'description' => 'Légumes frais.',
            'status' => Category::VALIDATED,
        ]);
        $this->fruits = Category::query()->create([
            'name' => 'Fruits',
            'description' => 'Fruits frais.',
            'status' => Category::VALIDATED,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'consommateur']));
    }

    public function test_une_phrase_tunisienne_filtre_categorie_origine_et_prix(): void
    {
        $attendue = $this->produit('Tomate de saison', $this->legumes, 4.5, 'Nabeul, Tunisie');
        $this->produit('Tomate chere', $this->legumes, 9, 'Nabeul, Tunisie');
        $this->produit('Tomate de Sfax', $this->legumes, 3, 'Sfax, Tunisie');
        $this->produit('Orange de Nabeul', $this->fruits, 2, 'Nabeul, Tunisie');
        $this->produit('Tomate cachee', $this->legumes, 2, 'Nabeul, Tunisie', 'draft');

        $this->get(route('produits', ['q' => 'Nheb khodhra men Nabeul ma tfoutech 5 DT']))
            ->assertOk()
            ->assertSee('Tomate de saison')
            ->assertSee('origine Nabeul')
            ->assertSee('prix maximum 5 DT')
            ->assertDontSee('Tomate chere')
            ->assertDontSee('Tomate de Sfax')
            ->assertDontSee('Orange de Nabeul')
            ->assertDontSee('Tomate cachee');

        $this->assertSame($attendue->status, Product::PUBLISHED);
    }

    public function test_les_fruits_de_sfax_ignorent_un_mot_sans_colonne(): void
    {
        $this->produit('Orange maltaise', $this->fruits, 3, 'Sfax, Tunisie');
        $this->produit('Tomate sfaxienne', $this->legumes, 3, 'Sfax, Tunisie');
        $this->produit('Pomme de Tunis', $this->fruits, 4, 'Tunis, Tunisie');

        $this->get(route('produits', ['q' => 'Fruits frais de Sfax']))
            ->assertOk()
            ->assertSee('Orange maltaise')
            ->assertSee('origine Sfax')
            ->assertDontSee('Tomate sfaxienne')
            ->assertDontSee('Pomme de Tunis');
    }

    public function test_les_tomates_pas_cheres_sont_triees_par_prix(): void
    {
        $this->produit('Tomate premium', $this->legumes, 12, 'Béja, Tunisie');
        $this->produit('Tomate du marche', $this->legumes, 2.5, 'Béja, Tunisie');
        $this->produit('Tomate moyenne', $this->legumes, 6, 'Béja, Tunisie');

        $this->get(route('produits', ['q' => 'Tomates pas chères']))
            ->assertOk()
            ->assertSeeInOrder(['Tomate du marche', 'Tomate moyenne', 'Tomate premium'])
            ->assertSee('les moins chers');
    }

    public function test_les_legumes_a_moins_de_dix_dinars_restent_des_legumes(): void
    {
        $this->produit('Salade locale', $this->legumes, 4, 'Ariana, Tunisie');
        $this->produit('Piment hors budget', $this->legumes, 15, 'Ariana, Tunisie');
        $this->produit('Fraise locale', $this->fruits, 6, 'Ariana, Tunisie');

        $this->get(route('produits', ['q' => 'Légumes locaux à moins de 10 DT']))
            ->assertOk()
            ->assertSee('Salade locale')
            ->assertSee('prix maximum 10 DT')
            ->assertDontSee('Piment hors budget')
            ->assertDontSee('Fraise locale');
    }

    public function test_une_faute_de_frappe_trouve_la_tomate_sans_meilisearch(): void
    {
        $this->produit('Tomate ancienne', $this->legumes, 3, 'Cap Bon, Tunisie');
        $this->produit('Huile d olive', $this->legumes, 8, 'Sfax, Tunisie');

        $this->get(route('produits', ['q' => 'tomatte']))
            ->assertOk()
            ->assertSee('Tomate ancienne')
            ->assertDontSee('Huile d olive');
    }

    public function test_la_recherche_classique_par_nom_reste_en_place(): void
    {
        $this->produit('Citron vert', $this->fruits, 2, 'Nabeul, Tunisie');

        $this->get(route('produits', ['q' => 'Citron']))
            ->assertOk()
            ->assertSee('Citron vert');
    }

    public function test_le_filtre_categorie_et_la_pagination_ne_montrent_que_les_publies(): void
    {
        foreach (range(1, 13) as $numero) {
            $produit = $this->produit('Pomme '.str_pad((string) $numero, 2, '0', STR_PAD_LEFT), $this->fruits, 3, 'Sfax, Tunisie');
            $produit->forceFill(['created_at' => now()->addMinutes($numero)])->save();
        }
        $this->produit('Pomme brouillon', $this->fruits, 3, 'Sfax, Tunisie', 'draft');

        $this->get(route('produits', ['q' => 'pomme', 'page' => 2]))
            ->assertOk()
            ->assertSee('Pomme 01')
            ->assertDontSee('Pomme 13')
            ->assertDontSee('Pomme brouillon');

        $this->get(route('produits', ['category_id' => $this->legumes->id]))
            ->assertOk()
            ->assertDontSee('Pomme 01');
    }

    public function test_les_suggestions_comprennent_la_phrase(): void
    {
        $this->produit('Tomate de saison', $this->legumes, 4, 'Nabeul, Tunisie');
        $this->produit('Orange maltaise', $this->fruits, 3, 'Sfax, Tunisie');

        $this->getJson(route('produits.suggestions', ['q' => 'khodhra men Nabeul']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Tomate de saison'])
            ->assertJsonMissing(['name' => 'Orange maltaise']);
    }

    public function test_meilisearch_indisponible_laisse_la_recherche_classique(): void
    {
        $this->produit('Tomate ancienne', $this->legumes, 3, 'Nabeul, Tunisie');
        config([
            'scout.driver' => 'meilisearch',
            'scout.meilisearch.host' => 'http://meilisearch.test',
        ]);
        Http::fake([
            'meilisearch.test/*' => Http::response('arret', 500),
        ]);

        $this->get(route('produits', ['q' => 'Tomate']))
            ->assertOk()
            ->assertSee('Tomate ancienne');
    }

    public function test_la_similarite_retrouve_un_produit_aux_mots_differents(): void
    {
        config([
            'services.gemini.key' => 'cle-de-test',
            'services.gemini.embedding_model' => 'gemini-embedding-001',
        ]);
        $this->produit('Salade romaine', $this->legumes, 3, 'Manouba, Tunisie', 'published', 'feuilles vertes');
        $this->produit('Huile d olive', $this->legumes, 8, 'Sfax, Tunisie', 'published', 'bouteille');

        Http::fake([
            'generativelanguage.googleapis.com/*batchEmbedContents' => Http::response([
                'embeddings' => [
                    ['values' => [1, 0]],
                    ['values' => [0, 1]],
                ],
            ]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [1, 0]],
            ]),
        ]);

        $this->get(route('produits', ['q' => 'crudites du soir']))
            ->assertOk()
            ->assertSee('Salade romaine')
            ->assertDontSee('Huile d olive');
    }

    private function produit(
        string $nom,
        Category $categorie,
        float $prix,
        string $origine,
        string $statut = 'published',
        string $description = 'Produit du catalogue.',
    ): Product {
        return Product::factory()->create([
            'name' => $nom,
            'description' => $description,
            'price' => $prix,
            'origin' => $origine,
            'ingredients' => $nom,
            'status' => $statut,
            'category_id' => $categorie->id,
        ]);
    }
}
