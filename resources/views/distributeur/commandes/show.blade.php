@extends('layouts.front')
@section('title', 'Commande à traiter')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Commande #{{ $commande->id }}</h1></div>
<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-4"><div class="card-body"><h5>Informations du transformateur</h5><p>{{ $commande->prenom }} {{ $commande->nom }} — {{ $commande->telephone }}</p><p class="mb-0">{{ $commande->adresse }}, {{ $commande->code_postal }} {{ $commande->ville }}</p><p>Commentaire : {{ $commande->commentaire ?: 'Aucun' }}</p>
    <h5>Produits</h5><ul>@foreach($commande->products as $product)<li>{{ $product->name }} × {{ $product->pivot->quantite }}</li>@endforeach</ul></div></div>
    <div class="d-flex gap-2">
        @if($commande->statut === 'confirmee' && !$commande->distributeur_id)<form method="POST" action="{{ route('distributeur.commandes.prendre-en-charge', $commande) }}">@csrf @method('PATCH')<button class="btn btn-primary">Prendre en charge</button></form>@endif
        @if($commande->statut === 'preparee' && $commande->distributeur_id === auth()->id() && !$commande->livraison)<a class="btn btn-success" href="{{ route('distributeur.livraisons.create', $commande) }}">Créer livraison</a>@endif
    </div>
</div>
@endsection
