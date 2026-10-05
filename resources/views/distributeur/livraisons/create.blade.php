@extends('layouts.front')
@section('title', 'Créer une livraison')
@section('content')
<div class="container py-5"><h1>Créer la livraison de la commande #{{ $commande->id }}</h1>
<form method="POST" action="{{ route('distributeur.livraisons.store', $commande) }}" class="row g-3">@csrf
    @include('distributeur.livraisons._form', ['livraison' => null])
    <div class="col-12"><button class="btn btn-primary">Créer la livraison</button></div>
</form></div>
@endsection
