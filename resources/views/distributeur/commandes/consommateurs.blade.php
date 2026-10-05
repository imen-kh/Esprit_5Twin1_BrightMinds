@extends('layouts.front')

@section('title', 'Commandes des consommateurs')

@section('content')
@include('partials.front.page-header', ['titre' => 'Commandes des consommateurs'])

@php
    // Libellé et couleur Bootstrap de chaque statut de commande
    $statuts = [
        'en_attente' => ['En attente', 'warning'],
        'confirmee' => ['Confirmée', 'info'],
        'preparee' => ['Préparée', 'primary'],
        'expediee' => ['Expédiée', 'secondary'],
        'en_transit' => ['En transit', 'secondary'],
        'livree' => ['Livrée', 'success'],
        'refusee' => ['Refusée', 'danger'],
        'annulee' => ['Annulée', 'dark'],
    ];
@endphp

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <p class="mb-0">
            Commandes passées par les consommateurs : celles qui attendent un distributeur et celles que vous avez prises en charge.
        </p>
        {{-- Filtre par statut : formulaire GET, le critère reste dans l'URL et la pagination --}}
        <form method="GET" action="{{ route('distributeur.commandes.consommateurs') }}" class="d-flex gap-2">
            <select name="statut" class="form-select" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                @foreach (['confirmee', 'preparee', 'expediee', 'en_transit', 'livree'] as $cle)
                    <option value="{{ $cle }}" @selected($statut === $cle)>{{ $statuts[$cle][0] }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary text-white">Filtrer</button>
        </form>
    </div>

    <div class="table-responsive border rounded">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Consommateur</th>
                    <th>Ville</th>
                    <th>Date</th>
                    <th class="text-center">Produits</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($commandes as $commande)
                    <tr>
                        <td class="fw-bold">#{{ $commande->id }}</td>
                        <td>
                            {{ $commande->prenom }} {{ $commande->nom }}
                            <small class="d-block text-muted">{{ $commande->user?->email }}</small>
                        </td>
                        <td>{{ $commande->ville }}</td>
                        <td>{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $commande->products->count() }}</td>
                        <td>{{ number_format((float) $commande->total, 3, ',', ' ') }} DT</td>
                        <td>
                            <span class="badge bg-{{ $statuts[$commande->statut][1] ?? 'secondary' }}">{{ $statuts[$commande->statut][0] ?? ucfirst($commande->statut) }}</span>
                            @if ($commande->distributeur_id === auth()->id())
                                <small class="d-block text-muted">Prise en charge par vous</small>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('distributeur.commandes.show', $commande) }}">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            @if ($statut)
                                Aucune commande de consommateur avec ce statut.
                            @else
                                Aucune commande de consommateur pour le moment.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $commandes->links('pagination::bootstrap-5') }}</div>

    <a class="btn border-secondary rounded-pill px-4 py-2 text-primary mt-3" href="{{ route('distributeur.dashboard') }}">Retour au tableau de bord</a>
</div>
@endsection
