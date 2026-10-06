<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->orderByRaw("CASE WHEN status = 'proposee' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:50|unique:categories,name',
            'description' => 'required|string|min:10|max:200',
        ], [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne peut pas dépasser 200 caractères.',
        ]);

        $data['status'] = Category::VALIDATED;
        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:50|unique:categories,name,'.$category->id,
            'description' => 'required|string|min:10|max:200',
        ], [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne peut pas dépasser 200 caractères.',
        ]);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie modifiée.');
    }

    public function approve(Category $category)
    {
        $category->update(['status' => Category::VALIDATED]);

        return back()->with('success', 'Catégorie validée.');
    }

    public function reject(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Cette catégorie contient des produits et ne peut pas être refusée.']);
        }

        $category->delete();

        return back()->with('success', 'Catégorie refusée.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Cette catégorie contient des produits et ne peut pas être supprimée.']);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée.');
    }
}
