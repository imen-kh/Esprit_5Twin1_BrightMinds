@extends('layouts.back')

@section('title', 'Produits')

@section('content')
<h1 class="h3 mb-4">Produits du catalogue</h1>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Produit</th><th>Catégorie</th><th>Acteur</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($products as $product)
        <tr>
            <td>{{ $product->name }}</td><td>{{ $product->category->name }}</td><td>{{ $product->user->name }}</td>
            <td>{{ $product->status === 'published' ? 'Publié' : 'Brouillon' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.produits.statut', $product) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-warning">{{ $product->status === 'published' ? 'Dépublier' : 'Publier' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Aucun produit enregistré.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
