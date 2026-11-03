@extends('layouts.front')

@section('title', 'Mes réclamations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Mes réclamations'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Suivi de vos réclamations</h2>
            <p class="text-muted mb-0">Chaque dossier a une référence, un délai de traitement et l'historique complet des échanges.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('avis.index') }}" class="btn btn-outline-primary rounded-pill"><i class="fas fa-star me-2"></i>Mes avis</a>
            @can('create', \App\Models\Reclamation::class)
                <a href="{{ route('reclamations.create') }}" class="btn btn-primary text-white rounded-pill"><i class="fas fa-plus me-2"></i>Nouvelle réclamation</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['total', 'Réclamations', 'fa-folder-open', null],
            ['ouvertes', 'En cours de traitement', 'fa-hourglass-half', 'en_cours'],
            ['resolues', 'Résolues', 'fa-check-circle', 'resolue'],
            ['a_evaluer', 'À évaluer', 'fa-star', null],
        ] as [$cle, $libelle, $icone, $filtre])
            <div class="col-6 col-lg-3">
                <a href="{{ $filtre ? route('reclamations.index', ['statut' => $filtre]) : route('reclamations.index') }}" class="text-decoration-none">
                    <div class="ra-tuile">
                        <i class="fas {{ $icone }} text-primary mb-2"></i>
                        <div class="valeur">{{ $compteurs[$cle] }}</div>
                        <div class="libelle">{{ $libelle }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if ($notifications->isNotEmpty())
        <div class="ra-carte p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="fas fa-bell text-secondary me-2"></i>Nouveautés</strong>
                <form method="POST" action="{{ route('notifications.tout-lire') }}">
                    @csrf
                    <button class="btn btn-sm btn-link text-decoration-none">Tout marquer comme lu</button>
                </form>
            </div>
            <ul class="list-unstyled mb-0">
                @foreach ($notifications as $notification)
                    <li class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                        <a href="{{ route('notifications.lire', $notification->id) }}" class="d-flex justify-content-between gap-2 text-decoration-none">
                            <span><strong class="text-dark">{{ $notification->data['titre'] ?? 'Notification' }}</strong> <span class="text-muted">— {{ $notification->data['message'] ?? '' }}</span></span>
                            <small class="text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('reclamations.index') }}" class="btn btn-sm rounded-pill {{ request('statut') ? 'btn-outline-secondary' : 'btn-secondary text-white' }}">Toutes</a>
        @foreach (\App\Models\Reclamation::statutsLibelles() as $valeur => $libelle)
            <a href="{{ route('reclamations.index', ['statut' => $valeur]) }}" class="btn btn-sm rounded-pill {{ request('statut') === $valeur ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">{{ $libelle }}</a>
        @endforeach
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse ($reclamations as $reclamation)
            <a href="{{ route('reclamations.show', $reclamation) }}" class="ra-avis text-decoration-none text-reset d-block">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div class="d-flex gap-3">
                        <span class="ra-avatar" style="background: var(--ra-orange)"><i class="fas {{ $reclamation->categorieIconeFront() }}"></i></span>
                        <div>
                            <div class="small text-muted"><span class="ra-reference">{{ $reclamation->reference }}</span> · {{ $reclamation->produit?->name ?? '—' }} · {{ $reclamation->created_at?->format('d/m/Y') }}</div>
                            <h6 class="mb-1">{{ $reclamation->sujet }}</h6>
                            <div class="small text-muted">{{ $reclamation->categorieLibelle() }}@if ($reclamation->source !== 'formulaire') · via {{ $reclamation->sourceLibelle() }}@endif</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span>
                        <span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span>
                        <div class="mt-1"><span class="badge {{ $reclamation->slaBadge() }} ra-sla"><i class="far fa-clock me-1"></i>{{ $reclamation->slaLibelle() }}</span></div>
                        @if ($reclamation->peutEtreEvaluee())
                            <div class="small text-secondary mt-1"><i class="fas fa-star me-1"></i>Donnez votre avis sur le traitement</div>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center text-muted py-5 ra-carte">
                <i class="far fa-smile fa-3x text-primary mb-3"></i>
                <p class="mb-1">Aucune réclamation pour le moment.</p>
                <p class="small mb-0">Un souci avec une commande ? Décrivez-le, ou parlez-en à <strong>Sahbi</strong>, notre assistant (bulle en bas à droite).</p>
            </div>
        @endforelse
    </div>

    @if ($reclamations->hasPages())
        <div class="mt-4">{{ $reclamations->links() }}</div>
    @endif
</div>
@endsection
