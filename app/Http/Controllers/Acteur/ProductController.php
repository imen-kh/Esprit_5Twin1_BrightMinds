<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\MatieresPremieresService;
use App\Services\ProductImageService;
use App\Services\ProduitIAService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        if (auth()->user()->isDistributeur()) {
            return redirect()->route('acteur.distribution');
        }

        // withCount('lots') : colonne « Lots » du transformateur (module Traçabilité)
        $query = Product::with('category')->withCount('lots')->orderByDesc('created_at');

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        if (auth()->user()->isTransformateur()) {
            $query->where('type_produit', 'transforme');
        }

        $products = $query->paginate(10);
        $orderedProducts = Product::with('category')
            ->whereHas('commandes', fn ($query) => $query->where('commandes.user_id', auth()->id()))
            ->orderBy('name')
            ->get();

        // Espace du transformateur : matières premières commandées, chiffres et dernières
        // transformations, fournis par le module Traçabilité
        $matieres = collect();
        $chiffres = null;
        $dernieresTransformations = collect();

        if (auth()->user()->isTransformateur()) {
            $service = app(MatieresPremieresService::class);
            $matieres = $service->pour(auth()->user());
            $chiffres = $service->chiffres(auth()->user(), $matieres);
            $dernieresTransformations = $service->dernieresTransformations(auth()->user());
        }

        return view('acteur.products.index', compact('products', 'orderedProducts', 'matieres', 'chiffres', 'dernieresTransformations'));
    }

    public function create()
    {
        $this->denyProductCreation();
        $categories = Category::validated()->orderBy('name')->get();

        return view('acteur.products.form', ['product' => new Product(), 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $this->denyProductCreation();
        $data = $this->validatedData($request);
        $data['quality_analysis'] = $this->rapportQualite($request);
        $data['image'] = $this->storeImage($request);
        $data['user_id'] = $request->user()->id;
        $data['status'] = Product::PENDING;
        $data['type_produit'] = $request->user()->isTransformateur() ? 'transforme' : 'matiere_premiere';
        Product::create($data);

        return redirect()->route('acteur.produits.index')->with('success', 'Produit soumis. Il sera publié dans le catalogue après validation par un administrateur.');
    }

    public function edit(Product $product)
    {
        $this->denyDistributor();
        $this->authorizeOwner($product);
        $categories = Category::validated()->orderBy('name')->get();

        return view('acteur.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->denyDistributor();
        $this->authorizeOwner($product);
        $data = $this->validatedData($request);
        if ($request->hasFile('image')) {
            $data['quality_analysis'] = $this->rapportQualite($request);
        }
        $data['image'] = $this->storeImage($request, $product);
        $data['type_produit'] = $request->user()->isTransformateur() ? 'transforme' : 'matiere_premiere';

        if (! $request->user()->isAdmin()) {
            $data['status'] = Product::PENDING;
            $data['review_note'] = null;
            $data['reviewed_at'] = null;
            $data['reviewed_by'] = null;
            $data['ai_analysis'] = null;
        }

        $product->update($data);

        return redirect()->route('acteur.produits.index')->with('success', 'Produit modifié et renvoyé en validation.');
    }

    public function destroy(Product $product)
    {
        $this->denyDistributor();
        $this->authorizeOwner($product);
        $image = $product->image;
        $product->delete();
        app(ProductImageService::class)->supprimer($image);

        return redirect()->route('acteur.produits.index')->with('success', 'Produit supprimé.');
    }

    /**
     * Gemini Vision propose une fiche à partir de la photo. Le producteur relit avant d'enregistrer.
     */
    public function genererIa(Request $request, ProduitIAService $ia): JsonResponse
    {
        $this->denyProductCreation();

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'image.required' => 'Choisissez une image avant de lancer l’analyse.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format JPG, PNG ou WEBP.',
            'image.max' => 'L’image ne peut pas dépasser 2 Mo.',
        ]);

        if (! $ia->estConfigure()) {
            return response()->json([
                'message' => 'L’IA n’est pas configurée. Saisissez le nom, la description et la catégorie manuellement.',
            ], 503);
        }

        $proposition = $ia->proposerDepuisImage(
            $request->file('image'),
            Category::validated()->orderBy('name')->get()
        );

        if ($proposition === null) {
            return response()->json([
                'message' => 'L’analyse de l’image est indisponible. Le formulaire reste utilisable.',
            ], 503);
        }

        return response()->json($proposition);
    }

    /**
     * Analyse visuelle indicative. N'enregistre rien et ne publie pas le produit.
     */
    public function qualiteIa(Request $request, ProduitIAService $ia): JsonResponse
    {
        $this->denyProductCreation();

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'image.required' => 'Choisissez une image avant d’analyser la qualité.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format JPG, PNG ou WEBP.',
            'image.max' => 'L’image ne peut pas dépasser 2 Mo.',
        ]);

        if (! $ia->estConfigure()) {
            return response()->json([
                'message' => 'L’IA n’est pas configurée. Vous pouvez enregistrer le produit sans analyse de qualité.',
            ], 503);
        }

        $rapport = $ia->evaluerQualite($request->file('image'));

        if ($rapport === null) {
            return response()->json([
                'message' => 'L’analyse de qualité est indisponible. Le formulaire reste utilisable.',
            ], 503);
        }

        $request->session()->put('produit_qualite_visuelle', [
            'empreinte' => hash('sha256', $request->file('image')->get()),
            'rapport' => $rapport,
        ]);

        return response()->json($rapport);
    }

    /**
     * Le rapport n'est conservé que s'il correspond exactement à la photo envoyée.
     *
     * @return array<string, mixed>|null
     */
    private function rapportQualite(Request $request): ?array
    {
        $memoire = $request->session()->pull('produit_qualite_visuelle');

        if (! $request->hasFile('image') || ! is_array($memoire) || ! is_array($memoire['rapport'] ?? null)) {
            return null;
        }

        $empreinte = hash('sha256', $request->file('image')->get());

        return ($memoire['empreinte'] ?? null) === $empreinte ? $memoire['rapport'] : null;
    }

    private function validatedData(Request $request): array
    {
        $rules = [
            'name' => 'required|string|min:3|max:100',
            'description' => 'required|string|min:10|max:1000',
            'price' => 'required|numeric|min:0.1|max:9999',
            'origin' => 'required|string|min:3|max:150',
            'category_id' => 'required|exists:categories,id,status,'.Category::VALIDATED,
            'image' => ($request->isMethod('post') ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        if ($request->user()->type_acteur === 'transformateur') {
            $rules['ingredients'] = 'required|string|min:10|max:1000';
        }

        $data = $request->validate($rules, [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être au moins 0,1 DT.',
            'price.max' => 'Le prix ne peut pas dépasser 9999 DT.',
            'origin.required' => 'L\'origine est obligatoire.',
            'origin.min' => 'L\'origine doit contenir au moins 3 caractères.',
            'origin.max' => 'L\'origine ne peut pas dépasser 150 caractères.',
            'ingredients.required' => 'Les ingrédients sont obligatoires.',
            'ingredients.min' => 'Les ingrédients doivent contenir au moins 10 caractères.',
            'ingredients.max' => 'Les ingrédients ne peuvent pas dépasser 1000 caractères.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie choisie n\'existe pas.',
            'image.required' => 'L\'image du produit est obligatoire.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format JPG, PNG ou WEBP.',
            'image.max' => 'L\'image ne peut pas dépasser 2 Mo.',
        ]);

        if ($request->user()->type_acteur !== 'transformateur') {
            $data['ingredients'] = null;
        }

        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request, ?Product $product = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $product?->image;
        }

        // Photo envoyée sur Cloudinary (dossier nutritrace/products) : on garde son URL https,
        // puis on supprime l'ancienne photo (Cloudinary ou ancien fichier local)
        $images = app(ProductImageService::class);
        $url = $images->envoyer($request->file('image'));
        $images->supprimer($product?->image);

        return $url;
    }

    /**
     * Le producteur ajoute des matières premières, le transformateur des produits transformés ;
     * le distributeur ne crée pas de produit.
     */
    private function denyProductCreation(): void
    {
        if (auth()->user()->isDistributeur()) {
            throw new HttpResponseException(
                redirect()->route('acteur.distribution')
            );
        }
    }

    private function denyDistributor(): void
    {
        abort_unless(! auth()->user()->isDistributeur(), 403, 'Les distributeurs ne peuvent pas gérer les produits.');
    }

    private function authorizeOwner(Product $product): void
    {
        if (! auth()->user()->isAdmin() && $product->user_id !== auth()->id()) {
            abort(403, 'Vous ne pouvez gérer que vos propres produits.');
        }
    }
}
