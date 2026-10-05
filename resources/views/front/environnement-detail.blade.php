@extends('layouts.front')

@section('title', 'Détail Bilan : ' . $bilan->nomAffiche())

@section('content')
<!-- Header Page -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Analyse Écologique : {{ $bilan->nomAffiche() }}</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="{{ route('front.environnement.index') }}">Environnement</a></li>
        <li class="breadcrumb-item active text-white">{{ $bilan->nomAffiche() }}</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (auth()->user()?->isAdmin())
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="{{ route('front.environnement.edit', $bilan) }}" class="btn btn-primary">Modifier</a>
                <form action="{{ route('front.environnement.destroy', $bilan) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bilan environnemental ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Supprimer</button>
                </form>
                <a href="{{ route('front.environnement.index') }}" class="btn btn-outline-secondary">Retour à la liste</a>
            </div>
        @endif
        <div class="row g-4">
            <!-- Colonne Principale -->
            <div class="col-lg-8">
                <div class="card shadow-sm border rounded-3 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <span class="badge bg-light text-primary text-uppercase px-3 py-2">Cycle de vie certifié</span>
                        <span class="badge {{ $bilan->ecoScoreBadgeClass() }} fs-5 px-3 py-2">Éco-Score {{ $bilan->eco_score }}</span>
                    </div>

                    <h2 class="fw-bold mb-2">{{ $bilan->nomAffiche() }}</h2>
                    <p class="mb-2">
                        <span class="text-muted">Facteur d'émission :</span>
                        <strong>{{ $bilan->facteurEmission?->nom ?? 'Non renseigné' }}</strong>
                    </p>
                    <p class="text-muted mb-4">
                        <i class="fas fa-map-marker-alt text-danger me-1"></i> Origine : <strong>{{ $bilan->origine_geographique ?: 'Non spécifiée' }}</strong>
                        • Distance : <strong>{{ $bilan->mesure('distance_km', 0) }} km</strong>
                        • Mode de transport : <strong>{{ ucfirst($bilan->mode_transport ?: 'camion') }}</strong>
                    </p>

                    <div class="p-4 bg-light rounded text-center mb-4">
                        <h5 class="text-muted text-uppercase mb-2">Note globale de durabilité</h5>
                        <div class="display-4 fw-bold text-dark mb-1">{{ (int) $bilan->score_numerique }} / 100</div>
                        <p class="text-secondary mb-2 fw-semibold">{{ $bilan->ecoScoreAppreciation() }}</p>
                        <span class="badge {{ $bilan->scoreBadgeClass() }}">{{ $bilan->scoreAppreciation() }}</span>
                    </div>

                    <h5 class="fw-bold mb-3">Émission CO2 : {{ $bilan->mesure('co2_total') }} kg CO2e</h5>
                    @php($parts = $bilan->repartitionCo2())
                    @if (array_sum($parts) > 0)
                        <div class="progress mb-3" style="height: 25px;">
                            @php
                                $facteursBarre = [
                                    'production' => $bilan->facteurProduction?->nom,
                                    'transport' => $bilan->facteurTransport?->nom,
                                    'emballage' => $bilan->facteurEmballage?->nom,
                                ];
                            @endphp
                            @foreach (['production' => ['Production', 'bg-success'], 'transport' => ['Transport', 'bg-info'], 'emballage' => ['Emballage', 'bg-warning text-dark']] as $cle => [$libelle, $classe])
                                @if ($parts[$cle] > 0)
                                    <div class="progress-bar {{ $classe }}" style="width: {{ $parts[$cle] }}%" title="{{ $libelle }}{{ $facteursBarre[$cle] ? ' — '.$facteursBarre[$cle] : '' }} : {{ $parts[$cle] }}%">
                                        @if ($parts[$cle] >= 14){{ $libelle }} ({{ $parts[$cle] }}%)@endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="row text-center mt-3 g-2">
                        <div class="col-md-4">
                            <div class="border rounded p-2 h-100">
                                <small class="text-muted d-block">Production</small>
                                <strong class="text-success">{{ $bilan->mesure('co2_production') }} kg</strong>
                                @if ($bilan->facteurProduction)
                                    <div class="small text-muted mt-1">{{ $bilan->facteurProduction->nom }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 h-100">
                                <small class="text-muted d-block">Impact transport</small>
                                <strong class="text-info">{{ $bilan->mesure('co2_transport') }} kg</strong>
                                @if ($bilan->facteurTransport)
                                    <div class="small text-muted mt-1">{{ $bilan->facteurTransport->nom }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-2 h-100">
                                <small class="text-muted d-block">Emballage</small>
                                <strong class="text-warning">{{ $bilan->mesure('co2_emballage') }} kg</strong>
                                @if ($bilan->facteurEmballage)
                                    <div class="small text-muted mt-1">{{ $bilan->facteurEmballage->nom }}</div>
                                @endif
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

                    @if (! $bilan->alternative_locale && ! $bilan->recommandation_ia)
                        <p class="text-muted mb-0">Aucune recommandation n'est encore disponible pour ce produit.</p>
                    @endif
                </div>
            </div>

            <!-- Colonne Latérale -->
            <div class="col-lg-4">
                <div class="card shadow-sm border rounded-3 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-seedling text-primary me-2"></i> Indicateurs Clés</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 gap-3">
                            <span class="text-muted">Quantité évaluée</span>
                            <strong class="text-end">{{ $bilan->mesure('quantite_produit', 2) }} {{ $bilan->unite_mesure ?: 'kg' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Mode de culture</span>
                            <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $bilan->mode_culture ?: 'standard')) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Type d'emballage</span>
                            <span class="badge bg-warning text-dark">{{ ucfirst($bilan->type_emballage ?: 'aucun') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 gap-3">
                            <span class="text-muted">Consommation d'eau</span>
                            <strong>{{ $bilan->mesure('consommation_eau_l', 0) }} L</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 gap-3">
                            <span class="text-muted">Impact transport</span>
                            <strong>{{ $bilan->mesure('co2_transport') }} kg CO2e</strong>
                        </li>
                        <li class="list-group-item px-0">
                            <span class="text-muted d-block mb-1">Commentaire</span>
                            <span>{{ $bilan->commentaires ?: 'Aucun commentaire' }}</span>
                        </li>
                    </ul>

                    <div class="mt-4">
                        <a href="{{ route('front.environnement.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-1"></i> Retour au catalogue éco-score
                        </a>
                    </div>
                </div>

                @if ($autresBilans->isNotEmpty())
                    <div class="card shadow-sm border rounded-3 p-4">
                        <h5 class="fw-bold mb-3">Autres bilans</h5>
                        @foreach ($autresBilans as $autre)
                            <a href="{{ route('front.environnement.show', $autre) }}" class="d-flex justify-content-between align-items-center text-decoration-none {{ $loop->last ? '' : 'mb-3' }}">
                                <span class="text-dark">{{ $autre->nomAffiche() }}</span>
                                <span class="badge {{ $autre->ecoScoreBadgeClass() }}">{{ $autre->eco_score }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
