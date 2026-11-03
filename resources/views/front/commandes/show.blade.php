@extends('layouts.front')

@section('title', 'Détail de la commande')

@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Commande #{{ $commande->id }}</h1></div>
<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2>Détail de la commande</h2><p class="mb-0">{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y H:i') }}</p></div>
        <span class="badge bg-primary fs-6">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
    </div>
    <div class="card mb-4"><div class="card-body">
        <h5>Produits</h5>
        <div class="table-responsive"><table class="table"><thead><tr><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th></tr></thead><tbody>
        @foreach($commande->products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->pivot->quantite }}</td><td>{{ \App\Models\Product::formatAmount($product->pivot->prix_unitaire) }} DT</td><td>{{ \App\Models\Product::formatAmount($product->pivot->sous_total) }} DT</td></tr>@endforeach
        </tbody></table></div>
        <div class="text-end fw-bold">Total : {{ \App\Models\Product::formatAmount($commande->total) }} DT</div>
    </div></div>
    <div class="card"><div class="card-body"><h5>Livraison</h5>
        @if(!$commande->livraison)<p class="mb-0">Livraison en attente.</p>@else
            <p>Statut : <strong>{{ ucfirst(str_replace('_', ' ', $commande->livraison->statut)) }}</strong></p>
            <p class="mb-0">Suivi : {{ $commande->livraison->numero_suivi ?: 'Non renseigné' }}</p>
            <p class="mb-0">Date prévue : {{ $commande->livraison->date_livraison_prevue?->format('d/m/Y') ?: 'Non renseignée' }}</p>
            <p>Date réelle : {{ $commande->livraison->date_livraison?->format('d/m/Y H:i') ?: 'En attente' }}</p>
            <h6 class="mt-4">Suivi de livraison</h6>
            @forelse($commande->livraison->historique as $evenement)
                <p class="mb-1">✓ {{ ucfirst(str_replace('_', ' ', $evenement->nouveau_statut)) }} — {{ $evenement->date_changement->format('d/m/Y H:i') }}</p>
            @empty
                <p class="mb-0">Suivi en attente.</p>
            @endforelse
        @endif
    </div></div>
</div>
@endsection
