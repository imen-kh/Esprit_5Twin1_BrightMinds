@extends('layouts.front')
@section('title', 'Modifier livraison')
@section('content')<div class="container py-5"><h1>Modifier la livraison #{{ $livraison->id }}</h1><form method="POST" action="{{ route('distributeur.livraisons.update', $livraison) }}" class="row g-3">@csrf @method('PUT')@include('distributeur.livraisons._form', ['commande' => $livraison->commande])<div class="col-12"><button class="btn btn-primary">Enregistrer</button></div></form></div>@endsection
