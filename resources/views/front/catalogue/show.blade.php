@extends('layouts.front')

@section('title', $product->name)

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">{{ $product->name }}</h1>
</div>
<div class="container py-5">
    <a href="{{ route('produits') }}">← Retour aux produits</a>
    <div class="border rounded p-4 mt-3">
        <p><strong>Catégorie :</strong> {{ $product->category->name }}</p>
        <p><strong>Description :</strong> {{ $product->description ?: 'Aucune description renseignée.' }}</p>
        <p><strong>Origine :</strong> {{ $product->origin ?: 'Non renseignée' }}</p>
        @if ($product->ingredients)
            <p><strong>Ingrédients :</strong> {{ $product->ingredients }}</p>
        @endif
        <p><strong>{{ $product->user->type_acteur ? ucfirst($product->user->type_acteur) : 'Acteur' }} :</strong> {{ $product->user->name }}</p>
        <p class="fs-4 fw-bold">{{ number_format((float) $product->price, 3, ',', ' ') }} DT</p>
        
        <form action="{{ route('panier.add') }}" method="POST" class="mt-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="input-group quantity mb-3" style="width: 130px;">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-sm btn-minus rounded-circle bg-light border" onclick="this.parentNode.nextElementSibling.stepDown()">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
                <input type="number" name="quantite" class="form-control form-control-sm text-center border-0" value="1" min="1">
                <div class="input-group-btn">
                    <button type="button" class="btn btn-sm btn-plus rounded-circle bg-light border" onclick="this.parentNode.previousElementSibling.stepUp()">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn border border-secondary rounded-pill px-4 py-2 text-primary"><i class="fa fa-shopping-bag me-2 text-primary"></i> Ajouter au panier</button>
        </form>
    </div>
</div>
@endsection
