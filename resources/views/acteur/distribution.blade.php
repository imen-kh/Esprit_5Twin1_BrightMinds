@extends('layouts.front')

@section('title', 'Espace distribution')

@section('content')
@include('partials.front.page-header', ['titre' => 'Espace distribution'])

<div class="container py-5">
    <p class="mb-4">Consultez les produits publiés. Le distributeur ne déclare pas de matière première.</p>
    <div class="row g-4">
        @forelse ($products as $product)
            <div class="col-md-4">
                <div class="border rounded h-100 overflow-hidden">
                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-100" style="height: 180px; object-fit: cover;">
                    <div class="p-4">
                    <span class="badge bg-secondary">{{ $product->category->name }}</span>
                    <h2 class="h5 mt-3">{{ $product->name }}</h2>
                    <p>{{ $product->description }}</p>
                    <p><strong>Origine :</strong> {{ $product->origin ?: 'Non renseignée' }}</p>
                    <p><strong>Acteur :</strong> {{ $product->user->name }}</p>
                    <p class="fw-bold">{{ $product->formattedPrice() }} DT</p>
                    <a class="btn btn-outline-primary" href="{{ route('produit.detail', $product) }}">Voir le produit</a>
                    </div>
                </div>
            </div>
        @empty
            <p>Aucun produit publié pour le moment.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
