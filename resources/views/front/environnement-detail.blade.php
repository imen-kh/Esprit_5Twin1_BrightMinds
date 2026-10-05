@extends('layouts.front')

@section('title', 'Détail Bilan : ' . $bilan->nom_produit)

@section('content')
<!-- Header Page -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Analyse Écologique : {{ $bilan->nom_produit }}</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="{{ route('front.environnement.index') }}">Environnement</a></li>
        <li class="breadcrumb-item active text-white">{{ $bilan->nom_produit }}</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-4">
        <div class="row g-4">
            <!-- Colonne Principale -->
            <div class="col-lg-8">
                <div class="card shadow-sm border rounded-3 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <span class="badge bg-light text-primary text-uppercase px-3 py-2">Cycle de vie certifié</span>
                        <span class="badge {{ $bilan->ecoScoreBadgeClass() }} fs-5 px-3 py-2">Éco-Score {{ $bilan->eco_score }}</span>
                    </div>

                    <h2 class="fw-bold mb-2">{{ $bilan->nom_produit }}</h2>
                    <p class="text-muted mb-4">
                        <i class="fas fa-map-marker-alt text-danger me-1"></i> Origine : <strong>{{ $bilan->origine_geographique ?: 'Non spécifiée' }}</strong>
                        • Distance : <strong>{{ $bilan->distance_km }} km</strong>
                        • Mode de transport : <strong>{{ ucfirst($bilan->mode_transport ?: 'camion') }}</strong>
                    </p>

                    <div class="p-4 bg-light rounded text-center mb-4">
                        <h5 class="text-muted text-uppercase mb-2">Note globale de durabilité</h5>
                        <div class="display-4 fw-bold text-dark mb-1">{{ $bilan->score_numerique }} / 100</div>
                        <p class="text-secondary mb-0 fw-semibold">{{ $bilan->ecoScoreAppreciation() }}</p>
                    </div>

                    <!-- Bilan Carbone Décomposé -->
                    <h5 class="fw-bold mb-3">Empreinte Carbone : {{ number_format($bilan->co2_total, 3, ',', ' ') }} kg CO2e</h5>
                    @php
                        $total = max(0.001, (float) $bilan->co2_total);
                        $pProd = round(($bilan->co2_production / $total) * 100);
                        $pTrsp = round(($bilan->co2_transport / $total) * 100);
                        $pEmb  = round(($bilan->co2_emballage / $total) * 100);
                    @endphp
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: {{ $pProd }}%">Production ({{ $pProd }}%)</div>
                        <div class="progress-bar bg-info" style="width: {{ $pTrsp }}%">Transport ({{ $pTrsp }}%)</div>
                        <div class="progress-bar bg-warning" style="width: {{ $pEmb }}%">Emballage ({{ $pEmb }}%)</div>
                    </div>

                    <div class="row text-center mt-3 g-2">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Production</small>
                                <strong class="text-success">{{ number_format($bilan->co2_production, 3) }} kg</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Transport</small>
                                <strong class="text-info">{{ number_format($bilan->co2_transport, 3) }} kg</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">Emballage</small>
                                <strong class="text-warning">{{ number_format($bilan->co2_emballage, 3) }} kg</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommandation IA & Alternative Locale -->
                <div class="card border-success shadow-sm p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="text-success fw-bold mb-0">
                            <i class="fas fa-robot me-2"></i> Recommandation IA & Alternative Locale
                        </h4>
                        @if ($bilan->reduction_co2_estimee_pourcent)
                            <span class="badge bg-success fs-6">-{{ $bilan->reduction_co2_estimee_pourcent }}% CO2</span>
                        @endif
                    </div>

                    @if ($bilan->alternative_locale)
                        <div class="p-3 bg-light rounded border border-success mb-3">
                            <h6 class="text-success fw-bold mb-2"><i class="fas fa-check-circle me-1"></i> Alternative locale identifiée :</h6>
                            <p class="mb-0 text-dark">{{ $bilan->alternative_locale }}</p>
                        </div>
                    @endif

                    @if ($bilan->recommandation_ia)
                        <div class="p-3 bg-white rounded border">
                            <h6 class="text-dark fw-bold mb-2"><i class="fas fa-lightbulb text-warning me-1"></i> Conseil transition écologique :</h6>
                            <p class="mb-0 text-muted small">{{ $bilan->recommandation_ia }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Colonne Latérale -->
            <div class="col-lg-4">
                <div class="card shadow-sm border rounded-3 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-seedling text-primary me-2"></i> Indicateurs Clés</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Quantité évaluée</span>
                            <strong>{{ $bilan->quantite_produit }} {{ $bilan->unite_mesure }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Mode de culture</span>
                            <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $bilan->mode_culture ?: 'standard')) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Type d'emballage</span>
                            <span class="badge bg-warning text-dark">{{ ucfirst($bilan->type_emballage ?: 'aucun') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Consommation d'eau</span>
                            <strong>{{ number_format($bilan->consommation_eau_l, 0) }} L</strong>
                        </li>
                    </ul>

                    <div class="mt-4">
                        <a href="{{ route('front.environnement.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-1"></i> Retour au catalogue éco-score
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
