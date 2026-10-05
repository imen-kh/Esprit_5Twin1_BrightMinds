@extends('layouts.front')

@section('title', 'Commandes à traiter')

@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Commandes à traiter</h1></div>

@php
    // Libellé et couleur Bootstrap des statuts affichés sur cette page
    $statuts = [
        'confirmee' => ['Confirmée', 'info'],
        'preparee' => ['Préparée', 'primary'],
    ];
@endphp

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filtre par type de client : consommateurs et transformateurs passent tous deux des commandes --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <span class="me-2">Afficher :</span>
        @foreach (['' => 'Toutes les commandes', 'consommateur' => 'Consommateurs', 'transformateur' => 'Transformateurs'] as $valeur => $libelle)
            <a
                href="{{ route('distributeur.commandes.index', $valeur ? ['client' => $valeur] : []) }}"
                class="btn btn-sm rounded-pill px-3 {{ (string) $client === (string) $valeur ? 'btn-primary text-white' : 'btn-outline-primary' }}">
                {{ $libelle }}
            </a>
        @endforeach
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($commandes as $commande)
                    <tr>
                        <td>#{{ $commande->id }}</td>
                        <td>{{ $commande->user->name }}</td>
                        <td>
                            {{-- Le client est un consommateur ou un transformateur --}}
                            @if ($commande->user->role === 'consommateur')
                                <span class="badge bg-secondary">Consommateur</span>
                            @else
                                <span class="badge bg-success">{{ ucfirst($commande->user->type_acteur ?? 'acteur') }}</span>
                            @endif
                        </td>
                        <td>{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y') }}</td>
                        <td>{{ number_format($commande->total, 3, ',', ' ') }} DT</td>
                        <td>
                            <span class="badge bg-{{ $statuts[$commande->statut][1] ?? 'secondary' }}">{{ $statuts[$commande->statut][0] ?? ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
                        </td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('distributeur.commandes.show', $commande) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            @if ($client)
                                Aucune commande à traiter pour ce type de client.
                            @else
                                Aucune commande à traiter.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $commandes->links('pagination::bootstrap-5') }}
</div>
@endsection
