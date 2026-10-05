@extends('layouts.front')

@section('title', 'Espace transformation')

@section('content')
@include('partials.front.page-header', ['titre' => 'Espace transformation'])

<div class="container py-5">
    <div class="border rounded p-4 bg-light">
        <h2 class="h4 text-primary">Transformateur</h2>
        <p class="mb-0 fs-5">Le transformateur ne peut pas ajouter un produit. Maintenant il n'a pas de matières premières.</p>
    </div>
    <a class="btn btn-primary mt-4" href="{{ route('produits') }}">Voir le catalogue</a>
</div>
@endsection
