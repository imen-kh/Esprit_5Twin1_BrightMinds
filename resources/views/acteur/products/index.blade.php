@extends('layouts.front')

@section('title', 'Ajouter un produit')

@section('content')
@include('partials.front.page-header', ['titre' => 'Ajouter un produit'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
        <p class="mb-0">Vos produits publiés apparaissent ici et dans le catalogue.</p>
        <a class="btn btn-primary rounded-pill px-4 py-3" href="{{ route('acteur.produits.create') }}">Ajouter un produit</a>
    </div>

    <div class="row g-4">
        @forelse ($products as $product)
            <div class="col-md-6 col-xl-4">
                <div class="rounded bg-light h-100 overflow-hidden d-flex flex-column">
                    @if ($product->image)
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-100" style="height: 180px; object-fit: cover;">
                    @endif
                    <div class="p-4 d-flex flex-column flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-primary">{{ $product->category->name }}</span>
                        <span class="fs-5 fw-bold text-primary">{{ $product->formattedPrice() }} DT</span>
                    </div>
                    <h3 class="h4">{{ $product->name }}</h3>
                    <p class="mb-2">{{ \Illuminate\Support\Str::limit($product->description, 140) }}</p>
                    <p class="mb-4"><i class="fas fa-map-marker-alt text-primary me-2"></i>{{ $product->origin }}</p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a class="btn border border-secondary rounded-pill px-4 text-primary" href="{{ route('acteur.produits.edit', $product) }}">Modifier</a>
                        <form method="POST" action="{{ route('acteur.produits.destroy', $product) }}" onsubmit="return confirm('Supprimer ce produit ?');">
                            @csrf @method('DELETE')
                            <button class="btn border border-secondary rounded-pill px-4 text-danger" type="submit">Supprimer</button>
                        </form>
                    </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="bg-light rounded text-center p-5">
                    <h3 class="h4 text-primary">Aucun produit pour le moment</h3>
                    <p class="mb-4">Ajoutez votre premier produit pour le publier dans le catalogue.</p>
                    <a class="btn btn-primary rounded-pill px-4 py-3" href="{{ route('acteur.produits.create') }}">Ajouter un produit</a>
                </div>
            </div>
        @endforelse
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
                <tr><td>{{ $product->name }}</td><td>{{ $product->category?->name ?: '—' }}</td><td>{{ $product->formattedPrice() }} DT</td></tr>
            @empty
                <tr><td colspan="3" class="text-center">Aucun produit commandé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
