@extends('layouts.front')

@section('title', 'Comparer deux produits')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Comparer deux produits</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="{{ route('front.environnement.index') }}">Environnement</a></li>
        <li class="breadcrumb-item active text-white">Comparaison</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-4">
        <p class="text-muted mb-4">Choisissez deux bilans pour voir côte à côte la lettre, le score, le CO₂ et le gain du produit le plus sobre.</p>

        <form method="GET" action="{{ route('front.environnement.comparer') }}" class="row g-3 align-items-end mb-5">
            <div class="col-md-5">
                <label class="form-label fw-semibold" for="gauche">Premier produit</label>
                <select class="form-select" id="gauche" name="gauche" required>
                    <option value="">Choisir un bilan</option>
                    @foreach ($bilans as $bilan)
                        <option value="{{ $bilan->id }}" @selected($gauche?->id === $bilan->id)>{{ $bilan->nomAffiche() }} — {{ $bilan->eco_score }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold" for="droite">Second produit</label>
                <select class="form-select" id="droite" name="droite" required>
                    <option value="">Choisir un bilan</option>
                    @foreach ($bilans as $bilan)
                        <option value="{{ $bilan->id }}" @selected($droite?->id === $bilan->id)>{{ $bilan->nomAffiche() }} — {{ $bilan->eco_score }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Comparer</button>
            </div>
        </form>

        @if ($identiques)
            <div class="alert alert-warning">Choisissez deux bilans différents.</div>
        @elseif ($gauche && $droite)
            <div class="alert alert-success">
                <strong>{{ $meilleur->nomAffiche() }}</strong>
                émet {{ $gain }} % de CO₂ en moins que l'autre produit.
            </div>

            <div class="row g-4">
                @foreach (['gauche' => $gauche, 'droite' => $droite] as $colonne)
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm {{ $colonne->is($meilleur) ? 'border-success' : '' }}">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="h5 fw-bold mb-0">{{ $colonne->nomAffiche() }}</h3>
                                    <span class="badge {{ $colonne->ecoScoreBadgeClass() }} fs-5">{{ $colonne->eco_score }}</span>
                                </div>
                                <p class="text-muted small">{{ $colonne->origine_geographique ?: 'Origine non précisée' }} · {{ $colonne->mesure('distance_km', 0) }} km</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex justify-content-between py-2 border-bottom"><span>Score</span><strong>{{ $colonne->score_numerique }}/100</strong></li>
                                    <li class="d-flex justify-content-between py-2 border-bottom"><span>CO₂ total</span><strong>{{ $colonne->mesure('co2_total') }} kg</strong></li>
                                    <li class="d-flex justify-content-between py-2 border-bottom"><span>Production</span><strong>{{ $colonne->mesure('co2_production') }} kg</strong></li>
                                    <li class="d-flex justify-content-between py-2 border-bottom"><span>Transport</span><strong>{{ $colonne->mesure('co2_transport') }} kg</strong></li>
                                    <li class="d-flex justify-content-between py-2"><span>Emballage</span><strong>{{ $colonne->mesure('co2_emballage') }} kg</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($bilans->isEmpty())
            <p class="text-muted">Aucun bilan validé à comparer pour le moment.</p>
        @endif

        <a href="{{ route('front.environnement.index') }}" class="btn btn-outline-secondary mt-4">Retour au classement</a>
    </div>
</div>
@endsection
