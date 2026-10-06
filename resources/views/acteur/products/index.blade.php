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
        @if (auth()->user()->type_acteur === 'transformateur')
            <p class="mb-0">Le transformateur ne peut pas ajouter un produit. Maintenant il n'a pas de matières premières.</p>
        @else
            <p class="mb-0">Gérez les produits de votre exploitation.</p>
            <a class="btn btn-primary" href="{{ route('acteur.produits.create') }}">Ajouter un produit</a>
        @endif
    </div>
    <div class="table-responsive border rounded">
        <table class="table mb-0">
            <thead>
                <tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name }}</td>
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
                <tr><td colspan="4">Aucun produit enregistré.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
