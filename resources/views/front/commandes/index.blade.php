@extends('layouts.front')

@section('title', 'Mes Commandes')

@section('content')
<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Mes Commandes</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="#">Pages</a></li>
        <li class="breadcrumb-item active text-white">Mes Commandes</li>
    </ol>
</div>
<!-- Single Page Header End -->

<div class="container-fluid py-5">
    <div class="container py-5">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <h1 class="mb-4">Historique de mes commandes</h1>
        <div class="table-responsive">
            <table class="table">
                <thead>
                  <tr>
                    <th scope="col">Commande #</th>
                    <th scope="col">Date</th>
                    <th scope="col">Nb Produits</th>
                    <th scope="col">Total</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Livraison</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                    @forelse($commandes as $commande)
                        <tr>
                            <td>
                                <p class="mb-0 mt-4">#{{ str_pad($commande->id, 5, '0', STR_PAD_LEFT) }}</p>
                            </td>
                            <td>
                                <p class="mb-0 mt-4">{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y à H:i') }}</p>
                            </td>
                            <td>
                                <p class="mb-0 mt-4">{{ $commande->products->sum('pivot.quantite') }}</p>
                            </td>
                            <td>
                                <p class="mb-0 mt-4">{{ number_format($commande->total, 3, ',', ' ') }} DT</p>
                            </td>
                            <td>
                                @php
                                    $statuts = [
                                        'en_attente' => ['En attente', 'warning'],
                                        'confirmee' => ['Confirmée', 'success'],
                                        'preparee' => ['Préparée', 'info'],
                                        'expediee' => ['Expédiée', 'primary'],
                                        'en_transit' => ['En transit', 'primary'],
                                        'livree' => ['Livrée', 'success'],
                                        'refusee' => ['Refusée', 'danger'],
                                        'annulee' => ['Annulée', 'danger'],
                                    ];
                                    [$statutLibelle, $statutClasse] = $statuts[$commande->statut] ?? [ucfirst($commande->statut), 'secondary'];
                                @endphp
                                <p class="mb-0 mt-4 text-{{ $statutClasse }} fw-bold">{{ $statutLibelle }}</p>
                            </td>
                            <td>
                                <p class="mb-0 mt-4">{{ $commande->livraison ? ucfirst(str_replace('_', ' ', $commande->livraison->statut)) : 'En attente' }}</p>
                            </td>
                            <td><a href="{{ route('commandes.show', $commande) }}" class="btn btn-sm btn-outline-primary mt-3">Voir</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Vous n'avez passé aucune commande.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
