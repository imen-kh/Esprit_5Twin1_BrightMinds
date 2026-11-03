@extends('layouts.front')

@section('title', 'Réclamations sur mes produits')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Retours clients'])

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Réclamations sur mes produits</h2>
            <p class="text-muted mb-0">Répondez aux clients et repérez les problèmes récurrents de vos produits.</p>
        </div>
        <a href="{{ route('acteur.avis.index') }}" class="btn btn-outline-primary rounded-pill"><i class="fas fa-star me-2"></i>Avis sur mes produits</a>
    </div>

    @if ($parCategorie->isNotEmpty())
        <div class="ra-carte p-4 mb-4">
            <h6 class="mb-3">Répartition par type de problème</h6>
            @php $max = max(1, $parCategorie->max()); @endphp
            <div class="row g-3">
                @foreach ($parCategorie->sortDesc() as $categorie => $total)
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="fas {{ \App\Models\Reclamation::CATEGORIES[$categorie]['fa'] ?? 'fa-circle' }} text-primary me-1"></i>{{ \App\Models\Reclamation::CATEGORIES[$categorie]['label'] ?? $categorie }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                        <div class="ra-barre"><span style="width: {{ round(100 * $total / $max) }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('acteur.reclamations.index') }}" class="btn btn-sm rounded-pill {{ request('statut') ? 'btn-outline-secondary' : 'btn-secondary text-white' }}">Toutes</a>
        @foreach (\App\Models\Reclamation::statutsLibelles() as $valeur => $libelle)
            <a href="{{ route('acteur.reclamations.index', ['statut' => $valeur]) }}" class="btn btn-sm rounded-pill {{ request('statut') === $valeur ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">{{ $libelle }}</a>
        @endforeach
    </div>

    <div class="table-responsive ra-carte">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Sujet</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Délai</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reclamations as $reclamation)
                    <tr>
                        <td class="ra-reference small">{{ $reclamation->reference }}</td>
                        <td>{{ $reclamation->sujet }}<div class="small text-muted">{{ $reclamation->user?->name }}</div></td>
                        <td>{{ $reclamation->produit?->name }}</td>
                        <td class="small">{{ $reclamation->categorieLibelle() }}</td>
                        <td><span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span></td>
                        <td><span class="badge {{ $reclamation->slaBadge() }} ra-sla">{{ $reclamation->slaLibelle() }}</span></td>
                        <td class="text-end"><a href="{{ route('reclamations.show', $reclamation) }}" class="btn btn-sm btn-outline-primary rounded-pill">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune réclamation sur vos produits. Bravo !</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reclamations->hasPages())
        <div class="mt-4">{{ $reclamations->links() }}</div>
    @endif
</div>
@endsection
