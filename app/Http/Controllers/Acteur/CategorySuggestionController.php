<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategorySuggestionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_name' => 'required|string|min:3|max:50|unique:categories,name',
            'category_description' => 'required|string|min:10|max:200',
        ], [
            'category_name.required' => 'Le nom de la catégorie est obligatoire.',
            'category_name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'category_name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'category_name.unique' => 'Cette catégorie existe déjà.',
            'category_description.required' => 'La description est obligatoire.',
            'category_description.min' => 'La description doit contenir au moins 10 caractères.',
            'category_description.max' => 'La description ne peut pas dépasser 200 caractères.',
        ]);

        Category::create([
            'name' => $data['category_name'],
            'description' => $data['category_description'],
            'status' => Category::PROPOSED,
            'origin' => Category::ORIGIN_PRODUCER,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Catégorie proposée. Elle apparaîtra dans la liste après validation par un administrateur.');
    }
};
