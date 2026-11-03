@extends('layouts.front')
@section('title', 'Détail de la commande')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Détail de la commande #{{ $commande->id }}</h1></div>
<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="text-muted">Commande reçue le {{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y à H:i') }}</span>
            <div class="mt-1">Client : <strong>{{ $commande->user?->role === 'consommateur' ? 'Consommateur' : 'Transformateur' }}</strong></div>
        </div>
        @php
            $statusLabels = ['confirmee' => ['Confirmée', 'info'], 'preparee' => ['Préparée', 'primary'], 'expediee' => ['Expédiée', 'secondary'], 'en_transit' => ['En transit', 'warning'], 'livree' => ['Livrée', 'success']];
            [$statusLabel, $statusColor] = $statusLabels[$commande->statut] ?? [ucfirst(str_replace('_', ' ', $commande->statut)), 'secondary'];
        @endphp
        <span class="badge text-bg-{{ $statusColor }} px-3 py-2">{{ $statusLabel }}</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                <h2 class="h5">Informations du client</h2>
                <p class="mb-2"><strong>{{ $commande->prenom }} {{ $commande->nom }}</strong></p>
                <p class="mb-2"><i class="fas fa-phone me-2 text-primary"></i>{{ $commande->telephone }}</p>
                <p class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i>{{ $commande->adresse }}, {{ $commande->code_postal }} {{ $commande->ville }}</p>
                <p class="mb-0"><strong>Commentaire :</strong> {{ $commande->commentaire ?: 'Aucun' }}</p>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                <h2 class="h5">Produits à livrer</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th></tr></thead>
                        <tbody>
                            @foreach($commande->products as $product)
                                @php $sousTotal = $product->pivot->sous_total ?? ((float) $product->pivot->prix_unitaire * (int) $product->pivot->quantite); @endphp
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ number_format((float) $product->pivot->prix_unitaire, 3, ',', ' ') }} DT</td>
                                    <td>{{ $product->pivot->quantite }}</td>
                                    <td>{{ number_format((float) $sousTotal, 3, ',', ' ') }} DT</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-top mt-3 pt-3 text-end"><strong>Total commande : {{ number_format((float) $commande->total, 3, ',', ' ') }} DT</strong></div>
            </div></div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('distributeur.commandes.index') }}">Retour aux commandes</a>
        @if($commande->statut === 'confirmee' && !$commande->distributeur_id)<form method="POST" action="{{ route('distributeur.commandes.prendre-en-charge', $commande) }}">@csrf @method('PATCH')<button class="btn btn-primary">Prendre en charge</button></form>@endif
        @if($commande->statut === 'preparee' && $commande->distributeur_id === auth()->id() && !$commande->livraison)<a class="btn btn-success" href="{{ route('distributeur.livraisons.create', $commande) }}">Créer livraison</a>@endif
    </div>
</div>
@endsection
