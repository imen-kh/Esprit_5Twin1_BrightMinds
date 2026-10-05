@extends('layouts.front')

@section('title', 'Mes produits')

@section('content')
@include('partials.front.page-header', ['titre' => 'Mes produits'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif
    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="mb-0">Gérez vos produits transformés.</p>
        <a class="btn btn-primary" href="{{ route('acteur.produits.create') }}">Ajouter un produit transformé</a>
    </div>
    <div class="table-responsive border rounded">
        <table class="table mb-0">
            <thead>
                <tr><th>Produit transformé</th><th>Catégorie</th><th>Type</th><th>Prix</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?: '—' }}</td>
                    <td><span class="badge bg-success">Transformé</span></td>
                    <td>{{ number_format((float) $product->price, 3, ',', ' ') }} DT</td>
                    <td class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('acteur.produits.edit', $product) }}">Modifier</a>
                        <form method="POST" action="{{ route('acteur.produits.destroy', $product) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun produit transformé enregistré.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
        <div>
            <h2 class="h4 mb-1">Produits commandés</h2>
            <p class="text-muted mb-0">Les produits présents dans vos commandes.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('commandes.index') }}">Voir mes commandes</a>
    </div>
    <div class="table-responsive border rounded">
        <table class="table mb-0">
            <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix actuel</th></tr></thead>
            <tbody>
            @forelse ($orderedProducts as $product)
                <tr><td>{{ $product->name }}</td><td>{{ $product->category?->name ?: '—' }}</td><td>{{ number_format((float) $product->price, 3, ',', ' ') }} DT</td></tr>
            @empty
                <tr><td colspan="3" class="text-center">Aucun produit commandé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
