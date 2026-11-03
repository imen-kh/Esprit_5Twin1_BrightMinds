@extends('layouts.front')

@section('title', 'Mes avis')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Mes avis'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($aNoter->isNotEmpty())
        <div class="ra-carte-ia p-4 mb-4">
            <h5 class="mb-1"><i class="fas fa-star text-secondary me-2"></i>Produits reçus à noter</h5>
            <p class="text-muted">Votre avis aide les autres clients et les producteurs.</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($aNoter as $produit)
                    <a href="{{ route('produit.detail', $produit) }}#avis" class="btn btn-outline-primary rounded-pill btn-sm">{{ $produit->name }}</a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="d-flex flex-column gap-3">
        @forelse ($avis as $unAvis)
            <article class="ra-avis">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <a href="{{ route('produit.detail', $unAvis->produit) }}#avis-{{ $unAvis->id }}" class="h6 mb-0">{{ $unAvis->produit?->name }}</a>
                        <div class="small text-muted">{{ $unAvis->created_at?->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-end">
                        <x-etoiles :note="$unAvis->note" />
                        <div><span class="badge {{ $unAvis->statutBadge() }}">{{ $unAvis->statutLibelle() }}</span></div>
                    </div>
                </div>
                @if ($unAvis->titre)<h6 class="mt-2 mb-1">{{ $unAvis->titre }}</h6>@endif
                <p class="mb-2">{{ $unAvis->commentaire }}</p>
                @if ($unAvis->statut === 'en_moderation' && $unAvis->moderation_raison)
                    <p class="small text-warning mb-2"><i class="fas fa-hourglass-half me-1"></i>En relecture : {{ $unAvis->moderation_raison }}</p>
                @endif
                @if ($unAvis->reponse_producteur)
                    <div class="ra-reponse-producteur small mb-2"><strong>Réponse du producteur :</strong> {{ $unAvis->reponse_producteur }}</div>
                @endif
                <div class="d-flex gap-2 small">
                    <span class="text-muted"><i class="fas fa-thumbs-up me-1"></i>{{ $unAvis->utile_count }}</span>
                    <a href="{{ route('avis.edit', $unAvis) }}" class="ms-auto">Modifier</a>
                </div>
            </article>
        @empty
            <div class="text-center text-muted py-5">
                <i class="far fa-comment-dots fa-3x mb-3 text-primary"></i>
                <p class="mb-0">Vous n'avez encore publié aucun avis.</p>
            </div>
        @endforelse
    </div>

    @if ($avis->hasPages())
        <div class="mt-4">{{ $avis->links() }}</div>
    @endif
</div>
@endsection
