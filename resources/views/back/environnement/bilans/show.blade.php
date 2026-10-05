@extends('layouts.back')

@section('title', 'Bilan : ' . $bilan->nom_produit)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Bilan Environnemental Détaillé</h4>
    <p class="text-muted mb-0">Évaluation complète du cycle de vie et recommandations d'optimisation locale.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('admin.bilans-environnementaux.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Retour à la liste
    </a>
    <a href="{{ route('admin.bilans-environnementaux.edit', $bilan) }}" class="btn btn-primary">
      <i class="bx bx-edit-alt me-1"></i> Modifier
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Colonne Principale : Éco-Score & Bilan Carbone -->
  <div class="col-lg-8">
    <div class="card mb-4 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
          <div>
            <span class="badge bg-label-secondary text-uppercase mb-2">Bilan Produit</span>
            <h3 class="mb-1 fw-bold">{{ $bilan->nom_produit }}</h3>
            <p class="text-muted mb-0">
              <i class="bx bx-map-pin text-danger"></i> {{ $bilan->origine_geographique ?: 'Origine non spécifiée' }}
              • <strong>{{ $bilan->distance_km }} km</strong> de transport ({{ ucfirst($bilan->mode_transport ?: 'camion') }})
            </p>
          </div>
          <div class="text-end">
            @if ($bilan->statut === 'valide')
              <span class="badge bg-success px-3 py-2 fs-6"><i class="bx bx-check-circle me-1"></i> Bilan Validé</span>
            @elseif ($bilan->statut === 'en_attente')
              <span class="badge bg-warning px-3 py-2 fs-6"><i class="bx bx-time me-1"></i> En cours d'audit</span>
            @else
              <span class="badge bg-secondary px-3 py-2 fs-6">Archivé</span>
            @endif
          </div>
        </div>

        <!-- Jauge Visuelle Éco-Score de A à E -->
        <div class="p-4 bg-light rounded-3 text-center mb-4 border">
          <h6 class="text-uppercase text-muted mb-3 fw-semibold">Éco-Score Officiel du Produit</h6>

          <!-- Échelle graphique des 5 lettres A-B-C-D-E -->
          <div class="d-inline-flex align-items-center gap-2 p-2 rounded-pill bg-white shadow-sm border mb-3">
            <span class="px-3 py-2 rounded-circle fw-bold text-white fs-4" style="background-color: {{ $bilan->eco_score === 'A' ? '#1e7e34' : '#e0e0e0' }}; opacity: {{ $bilan->eco_score === 'A' ? '1' : '0.4' }};">A</span>
            <span class="px-3 py-2 rounded-circle fw-bold text-white fs-4" style="background-color: {{ $bilan->eco_score === 'B' ? '#28a745' : '#e0e0e0' }}; opacity: {{ $bilan->eco_score === 'B' ? '1' : '0.4' }};">B</span>
            <span class="px-3 py-2 rounded-circle fw-bold text-white fs-4" style="background-color: {{ $bilan->eco_score === 'C' ? '#ffc107' : '#e0e0e0' }}; opacity: {{ $bilan->eco_score === 'C' ? '1' : '0.4' }};">C</span>
            <span class="px-3 py-2 rounded-circle fw-bold text-white fs-4" style="background-color: {{ $bilan->eco_score === 'D' ? '#fd7e14' : '#e0e0e0' }}; opacity: {{ $bilan->eco_score === 'D' ? '1' : '0.4' }};">D</span>
            <span class="px-3 py-2 rounded-circle fw-bold text-white fs-4" style="background-color: {{ $bilan->eco_score === 'E' ? '#dc3545' : '#e0e0e0' }}; opacity: {{ $bilan->eco_score === 'E' ? '1' : '0.4' }};">E</span>
          </div>

          <h4 class="mb-1 fw-bold text-dark">{{ $bilan->ecoScoreAppreciation() }}</h4>
          <p class="text-muted mb-0">Score global de durabilité calculé : <strong>{{ $bilan->score_numerique }} / 100</strong></p>
        </div>

        <!-- Décomposition des émissions carbone -->
        <h5 class="mb-3">Empreinte Carbone : <strong class="text-primary">{{ number_format($bilan->co2_total, 3, ',', ' ') }} kg CO2e</strong> <small class="text-muted fs-6">pour {{ $bilan->quantite_produit }} {{ $bilan->unite_mesure }}</small></h5>

        @php
          $totalCo2 = max(0.001, (float) $bilan->co2_total);
          $pctProd = round(($bilan->co2_production / $totalCo2) * 100);
          $pctTrsp = round(($bilan->co2_transport / $totalCo2) * 100);
          $pctEmb = round(($bilan->co2_emballage / $totalCo2) * 100);
        @endphp

        <div class="progress mb-3" style="height: 24px;">
          <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctProd }}%" title="Production agricole : {{ $pctProd }}%">
            Production ({{ $pctProd }}%)
          </div>
          <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pctTrsp }}%" title="Transport : {{ $pctTrsp }}%">
            Transport ({{ $pctTrsp }}%)
          </div>
          <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pctEmb }}%" title="Emballage : {{ $pctEmb }}%">
            Emballage ({{ $pctEmb }}%)
          </div>
        </div>

        <div class="row text-center mt-3 g-2">
          <div class="col-4">
            <div class="border rounded p-2 bg-light">
              <small class="text-muted d-block">Production</small>
              <strong class="text-success fs-5">{{ number_format($bilan->co2_production, 3, ',', ' ') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded p-2 bg-light">
              <small class="text-muted d-block">Transport</small>
              <strong class="text-info fs-5">{{ number_format($bilan->co2_transport, 3, ',', ' ') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded p-2 bg-light">
              <small class="text-muted d-block">Emballage</small>
              <strong class="text-warning fs-5">{{ number_format($bilan->co2_emballage, 3, ',', ' ') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Encadré IA : Alternative plus locale & Recommandations -->
    <div class="card border-success shadow-sm mb-4">
      <div class="card-header bg-label-success d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bx bx-bot bx-sm text-success"></i>
          <h5 class="mb-0 text-success fw-bold">Recommandation IA — Alternative Plus Locale</h5>
        </div>
        @if ($bilan->reduction_co2_estimee_pourcent)
          <span class="badge bg-success fs-6">
            Gain estimé : -{{ $bilan->reduction_co2_estimee_pourcent }}% CO2
          </span>
        @endif
      </div>
      <div class="card-body pt-3">
        @if ($bilan->alternative_locale)
          <div class="p-3 bg-white rounded border border-success mb-3">
            <h6 class="text-success fw-bold mb-2"><i class="bx bx-check-circle me-1"></i> Solution locale identifiée :</h6>
            <p class="mb-0 text-dark fs-6" style="line-height: 1.6;">
              {{ $bilan->alternative_locale }}
            </p>
          </div>
        @endif

        @if ($bilan->recommandation_ia)
          <div class="p-3 bg-light rounded">
            <h6 class="text-dark fw-bold mb-2"><i class="bx bx-bulb text-warning me-1"></i> Pistes d'amélioration continue :</h6>
            <p class="mb-0 text-muted small">
              {{ $bilan->recommandation_ia }}
            </p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Colonne Latérale : Détails techniques & Facteurs mobilisés -->
  <div class="col-lg-4">
    <div class="card mb-4 shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="bx bx-list-check me-1 text-primary"></i> Paramètres du Cycle</h5>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">Quantité évaluée</span>
            <strong>{{ $bilan->quantite_produit }} {{ $bilan->unite_mesure }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">Mode de culture</span>
            <span class="badge bg-label-primary">{{ ucfirst(str_replace('_', ' ', $bilan->mode_culture ?: 'standard')) }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">Type emballage</span>
            <span class="badge bg-label-warning">{{ ucfirst($bilan->type_emballage ?: 'aucun') }} ({{ $bilan->poids_emballage_g }}g)</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">Transport</span>
            <span>{{ ucfirst($bilan->mode_transport ?: 'camion') }} • {{ $bilan->distance_km }} km</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">Consommation d'eau</span>
            <strong>{{ number_format($bilan->consommation_eau_l, 0) }} Litres</strong>
          </li>
        </ul>
      </div>
    </div>

    <!-- Facteurs d'émission rattachés -->
    <div class="card shadow-sm mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="bx bx-data me-1 text-info"></i> Coefficients Utilisés</h5>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush small">
          @if ($bilan->facteurProduction)
            <li class="list-group-item">
              <span class="text-muted d-block">Production :</span>
              <strong>{{ $bilan->facteurProduction->nom }}</strong>
              <div class="text-primary">{{ $bilan->facteurProduction->valeur }} {{ $bilan->facteurProduction->unite }}</div>
            </li>
          @endif
          @if ($bilan->facteurTransport)
            <li class="list-group-item">
              <span class="text-muted d-block">Transport :</span>
              <strong>{{ $bilan->facteurTransport->nom }}</strong>
              <div class="text-info">{{ $bilan->facteurTransport->valeur }} {{ $bilan->facteurTransport->unite }}</div>
            </li>
          @endif
          @if ($bilan->facteurEmballage)
            <li class="list-group-item">
              <span class="text-muted d-block">Emballage :</span>
              <strong>{{ $bilan->facteurEmballage->nom }}</strong>
              <div class="text-warning">{{ $bilan->facteurEmballage->valeur }} {{ $bilan->facteurEmballage->unite }}</div>
            </li>
          @endif
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
