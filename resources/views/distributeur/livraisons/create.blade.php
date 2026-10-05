@extends('layouts.front')
@section('title', 'Créer une livraison')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Créer une livraison</h1></div>
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-10 col-xl-9"><div class="card shadow-sm border-0"><div class="card-body p-4 p-md-5"><h1 class="h3 text-center mb-4">Créer la livraison de la commande #{{ $commande->id }}</h1>
<form method="POST" action="{{ route('distributeur.livraisons.store', $commande) }}" class="row g-3">@csrf
    @include('distributeur.livraisons._form', ['livraison' => null])
    <div class="col-12"><button class="btn btn-primary">Créer la livraison</button></div>
</form></div></div></div></div></div>
@endsection
