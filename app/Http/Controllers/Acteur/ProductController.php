<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        if (auth()->user()->isTransformateur()) {
            return redirect()->route('acteur.transformation');
        }

        if (auth()->user()->isDistributeur()) {
            return redirect()->route('acteur.distribution');
        }

        $query = Product::with('category')->orderByDesc('created_at');

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $products = $query->paginate(10);

        return view('acteur.products.index', compact('products'));
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
        $data['image'] = $this->storeImage($request);
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'published';
        Product::create($data);

        return redirect()->route('acteur.produits.index')->with('success', 'Produit ajouté. Il est visible dans le catalogue.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);
        $categories = Category::validated()->orderBy('name')->get();

        return view('acteur.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = $this->validatedData($request);
        $data['image'] = $this->storeImage($request, $product);
        $data['status'] = 'published';
        $product->update($data);

        return redirect()->route('acteur.produits.index')->with('success', 'Produit modifié.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('acteur.produits.index')->with('success', 'Produit supprimé.');
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

        if ($product?->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $request->file('image')->store('products', 'public');
    }

    private function denyProductCreation(): void
    {
        if (auth()->user()->isTransformateur()) {
            throw new HttpResponseException(
                redirect()->route('acteur.transformation')
            );
        }

        if (auth()->user()->isDistributeur()) {
            throw new HttpResponseException(
                redirect()->route('acteur.distribution')
            );
        }
    }

    private function authorizeOwner(Product $product): void
    {
        if (! auth()->user()->isAdmin() && $product->user_id !== auth()->id()) {
            abort(403, 'Vous ne pouvez gérer que vos propres produits.');
        }
    }
}
