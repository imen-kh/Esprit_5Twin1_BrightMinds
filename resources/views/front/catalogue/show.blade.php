@extends('layouts.front')

@section('title', $product->name)

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Détail du produit</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="{{ route('produits') }}">Produits</a></li>
        <li class="breadcrumb-item active text-white">{{ $product->name }}</li>
    </ol>
</div>

<div class="container-fluid vesitable py-5">
    <div class="container">
        <a class="text-primary" href="{{ route('produits') }}"><i class="fas fa-arrow-left me-2"></i>Retour aux produits</a>

        <div class="border border-primary rounded vesitable-item mt-4">
            <div class="row g-0 align-items-center">
                <div class="col-lg-6 position-relative">
                    <div class="vesitable-img bg-light">
                        @if ($product->image)
                            <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid w-100 rounded-top" alt="{{ $product->name }}" style="height: 420px; object-fit: contain;">
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="height: 420px;">
                                <i class="fas fa-leaf fa-4x text-primary"></i>
                            </div>
                        @endif
                    </div>
                    <div class="text-white bg-primary px-3 py-1 rounded position-absolute" style="top: 10px; right: 10px;">{{ $product->category->name }}</div>
                </div>
                <div class="col-lg-6 p-4 p-lg-5">
                    <h2 class="display-6 mb-3">{{ $product->name }}</h2>
                    <p class="fs-5">{{ $product->description ?: 'Aucune description renseignée.' }}</p>
                    @if ($product->ingredients)
                        <p><strong>Ingrédients :</strong> {{ $product->ingredients }}</p>
                    @endif
                    <p class="mb-2"><i class="fas fa-map-marker-alt text-primary me-2"></i>{{ $product->origin ?: 'Origine non renseignée' }}</p>
                    <p class="mb-4"><i class="fas fa-user text-primary me-2"></i>{{ $product->user->type_acteur ? ucfirst($product->user->type_acteur) : 'Acteur' }} : {{ $product->user->name }}</p>
                    <p class="text-dark fs-3 fw-bold mb-0">{{ $product->formattedPrice() }} DT</p>
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <h2 class="mt-5 mb-0">Autres produits</h2>
            <div class="owl-carousel product-carousel">
                @foreach ($related as $item)
                    @include('partials.front.product-card', ['product' => $item])
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const relatedCarousel = document.querySelector('.product-carousel');
if (relatedCarousel && typeof $ !== 'undefined') {
    const count = relatedCarousel.children.length;
    $(relatedCarousel).owlCarousel({
        autoplay: count > 1,
        smartSpeed: 1500,
        dots: true,
        loop: count > 3,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0: { items: 1 },
            768: { items: 2 },
            992: { items: 3 }
        }
    });
}
</script>
@endpush
