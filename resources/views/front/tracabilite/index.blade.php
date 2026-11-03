@extends('layouts.front')

@section('title', 'Traçabilité')

@section('content')
        @include('partials.front.page-header', ['titre' => 'Traçabilité'])

        <!-- Traçabilité Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">

                {{-- ===== Recherche d'un lot par son code ===== --}}
                <div class="p-5 bg-light rounded mb-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-6">
                            <h1 class="text-primary mb-3">Suivez votre produit, de la ferme à l'assiette</h1>
                            <p class="mb-0">
                                Chaque produit NutriTrace appartient à un lot. Saisissez le code imprimé sur l'emballage,
                                ou scannez son QR code, pour découvrir qui l'a produit, où il est passé et dans quelles conditions.
                            </p>
                        </div>
                        <div class="col-lg-6">
                            {{-- Formulaire GET : le code part dans l'URL (?code=…) --}}
                            <form method="GET" action="{{ route('tracabilite.index') }}" novalidate>
                                <label for="code" class="form-label">Code du lot</label>
                                <div class="position-relative">
                                    <input
                                        type="text"
                                        class="form-control py-3 rounded-pill @if ($introuvable) is-invalid @else border-0 @endif"
                                        id="code"
                                        name="code"
                                        value="{{ $code }}"
                                        placeholder="Ex. LOT-2026-0001"
                                        autocomplete="off">
                                    <button type="submit" class="btn btn-primary rounded-pill text-white position-absolute top-0 end-0 h-100 px-4">
                                        <i class="fas fa-search me-2"></i>Suivre
                                    </button>
                                </div>
                                @if ($introuvable)
                                    <div class="text-danger mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        Lot introuvable : aucun lot ne porte le code « {{ $code }} ».
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ===== Comment ça marche ===== --}}
                <div class="row g-4 mb-5">
                    @foreach ([
                        ['fa-seedling', 'Production', 'Le producteur déclare la récolte de chaque lot.'],
                        ['fa-industry', 'Transformation', 'Le transformateur relie son lot à la matière première.'],
                        ['fa-truck', 'Transport et stockage', 'Le distributeur enregistre les trajets et les températures.'],
                        ['fa-qrcode', 'Transparence', 'Vous vérifiez tout le parcours avec un simple code.'],
                    ] as [$icone, $titre, $texte])
                        <div class="col-md-6 col-lg-3">
                            <div class="bg-light rounded p-4 text-center h-100">
                                <i class="fas {{ $icone }} fa-2x text-primary mb-3"></i>
                                <h5>{{ $titre }}</h5>
                                <p class="mb-0">{{ $texte }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ===== Quelques lots récents, en exemple ===== --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h3 class="mb-1">Lots à découvrir</h3>
                        <p class="text-muted mb-0">Cliquez sur une fiche pour consulter le parcours et tester l'assistant IA.</p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        {{ $lotsRecents->count() }} lot(s) avec traçabilité
                    </span>
                </div>
                <div class="row g-4">
                    @forelse ($lotsRecents as $lot)
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('tracabilite.show', $lot) }}" class="d-block bg-light rounded p-4 h-100 tracabilite-exemple">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $lot->code }}</span>
                                    <span class="badge bg-{{ $lot->statut_badge }} rounded-pill px-3 py-2">{{ $lot->statut_label }}</span>
                                </div>
                                <h5 class="text-dark mb-1">{{ $lot->produit->name }}</h5>
                                <p class="text-muted mb-2">{{ $lot->produit->category?->name }}</p>
                                <p class="text-dark mb-0">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>Produit le {{ $lot->date_production->format('d/m/Y') }}
                                    <span class="mx-1">·</span>
                                    <i class="fas fa-route text-primary me-1"></i>{{ $lot->etapes_count }} étape(s)
                                </p>
                                <span class="btn btn-sm btn-primary rounded-pill text-white mt-3">
                                    Voir le parcours et tester l'IA
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </span>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="bg-light rounded p-4 text-center">Aucun lot disponible pour le moment.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <!-- Traçabilité End -->
@endsection

@push('styles')
    <style>
        .tracabilite-exemple { transition: transform .2s, box-shadow .2s; }
        .tracabilite-exemple:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1); }
    </style>
@endpush
