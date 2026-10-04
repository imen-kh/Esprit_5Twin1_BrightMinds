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
    </div>
</div>
@endsection
