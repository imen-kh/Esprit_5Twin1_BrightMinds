@extends('layouts.back')

@section('title', 'Bilan : ' . $bilan->nomAffiche())

@section('content')
@php($parts = $bilan->repartitionCo2())

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
  <div>
    <h4 class="mb-1">Bilan Environnemental Détaillé</h4>
    <p class="text-muted mb-0">Évaluation complète du cycle de vie et recommandations d'optimisation locale.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="{{ route('admin.bilans-environnementaux.index') }}" class="btn btn-outline-secondary">
      <i class="bx bx-arrow-back me-1"></i> Retour à la liste
    </a>
    @if ($bilan->statut === 'valide')
      <a href="{{ route('front.environnement.show', $bilan) }}" class="btn btn-outline-success" target="_blank" rel="noopener">
        <i class="bx bx-link-external me-1"></i> Voir sur le site
      </a>
    @endif
    <a href="{{ route('admin.bilans-environnementaux.edit', $bilan) }}" class="btn btn-primary">
      <i class="bx bx-edit-alt me-1"></i> Modifier
    </a>
    <form action="{{ route('admin.bilans-environnementaux.destroy', $bilan) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bilan environnemental ?');">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger">
        <i class="bx bx-trash me-1"></i> Supprimer
      </button>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
          <div>
            <span class="badge bg-label-secondary text-uppercase mb-2">Bilan Produit</span>
            <h3 class="mb-1 fw-bold">{{ $bilan->nomAffiche() }}</h3>
            <p class="mb-1">
              <span class="text-muted">Facteur d'émission :</span>
              <strong>{{ $bilan->facteurEmission?->nom ?? 'Non renseigné' }}</strong>
              @if ($bilan->facteurEmission)
                <span class="text-muted small">({{ $bilan->facteurEmission->valeur }} {{ $bilan->facteurEmission->unite }})</span>
              @endif
            </p>
            <p class="text-muted mb-0">
              <i class="bx bx-map-pin text-danger"></i> {{ $bilan->origine_geographique ?: 'Origine non spécifiée' }}
              • <strong>{{ $bilan->mesure('distance_km', 0) }} km</strong> de transport ({{ ucfirst($bilan->mode_transport ?: 'camion') }})
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

        <div class="p-4 bg-light rounded-3 text-center mb-4 border">
          <h6 class="text-uppercase text-muted mb-3 fw-semibold">Éco-Score Officiel du Produit</h6>

          <div class="d-inline-flex align-items-center gap-2 p-2 rounded-pill bg-white shadow-sm border mb-3">
            @foreach (['A' => '#1e7e34', 'B' => '#28a745', 'C' => '#ffc107', 'D' => '#fd7e14', 'E' => '#dc3545'] as $lettre => $couleur)
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold fs-4"
                    style="width: 2.75rem; height: 2.75rem; background-color: {{ $bilan->eco_score === $lettre ? $couleur : '#e0e0e0' }}; color: {{ $bilan->eco_score === $lettre && $lettre === 'C' ? '#212529' : '#fff' }}; opacity: {{ $bilan->eco_score === $lettre ? '1' : '0.45' }};">
                {{ $lettre }}
              </span>
            @endforeach
          </div>

          <h4 class="mb-1 fw-bold text-dark">{{ $bilan->ecoScoreAppreciation() }}</h4>
          <p class="text-muted mb-2">Score environnemental : <strong>{{ (int) $bilan->score_numerique }} / 100</strong></p>
          <span class="badge {{ $bilan->scoreBadgeClass() }}">{{ $bilan->scoreAppreciation() }}</span>
        </div>

        <h5 class="mb-3">
          Émission CO2 :
          <strong class="text-primary">{{ $bilan->mesure('co2_total') }} kg CO2e</strong>
          <small class="text-muted fs-6">pour {{ $bilan->mesure('quantite_produit', 2) }} {{ $bilan->unite_mesure ?: 'kg' }}</small>
        </h5>

        @if (array_sum($parts) === 0)
          <p class="text-muted mb-0">La décomposition production / transport / emballage n'est pas encore renseignée.</p>
        @else
          <div class="progress mb-3" style="height: 24px;">
            @foreach (['production' => ['Production', 'bg-success'], 'transport' => ['Transport', 'bg-info'], 'emballage' => ['Emballage', 'bg-warning']] as $cle => [$libelle, $classe])
              @if ($parts[$cle] > 0)
                <div class="progress-bar {{ $classe }} {{ $cle === 'emballage' ? 'text-dark' : '' }}" role="progressbar" style="width: {{ $parts[$cle] }}%" title="{{ $libelle }} : {{ $parts[$cle] }}%">
                  @if ($parts[$cle] >= 14)
                    {{ $libelle }} ({{ $parts[$cle] }}%)
                  @endif
                </div>
              @endif
            @endforeach
          </div>
        @endif

        <div class="row text-center mt-3 g-2">
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <small class="text-muted d-block">Production</small>
              <strong class="text-success fs-5">{{ $bilan->mesure('co2_production') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <small class="text-muted d-block">Impact transport</small>
              <strong class="text-info fs-5">{{ $bilan->mesure('co2_transport') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 bg-light h-100">
              <small class="text-muted d-block">Emballage</small>
              <strong class="text-warning fs-5">{{ $bilan->mesure('co2_emballage') }}</strong> <small>kg CO2e</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-success shadow-sm mb-4">
      <div class="card-header bg-label-success d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="bx bx-bot bx-sm text-success"></i>
          <h5 class="mb-0 text-success fw-bold">Recommandation IA — Alternative Plus Locale</h5>
        </div>
        @if ($bilan->reduction_co2_estimee_pourcent)
          <span class="badge bg-success fs-6">
            Gain estimé : -{{ $bilan->mesure('reduction_co2_estimee_pourcent', 1) }}% CO2
          </span>
        @endif
      </div>
      <div class="card-body pt-3">
        @if ($bilan->alternative_locale || $bilan->recommandation_ia)
          @if ($bilan->alternative_locale)
            <div class="p-3 bg-white rounded border border-success mb-3">
              <h6 class="text-success fw-bold mb-2"><i class="bx bx-check-circle me-1"></i> Solution locale identifiée :</h6>
              <p class="mb-0 text-dark fs-6" style="line-height: 1.6;">{{ $bilan->alternative_locale }}</p>
            </div>
          @endif

          @if ($bilan->recommandation_ia)
            <div class="p-3 bg-light rounded">
              <h6 class="text-dark fw-bold mb-2"><i class="bx bx-bulb text-warning me-1"></i> Pistes d'amélioration continue :</h6>
              <p class="mb-0 text-muted small">{{ $bilan->recommandation_ia }}</p>
            </div>
          @endif
        @else
          <p class="text-muted mb-0">Aucune recommandation n'a encore été calculée pour ce bilan.</p>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4 shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="bx bx-list-check me-1 text-primary"></i> Paramètres du Cycle</h5>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Quantité évaluée</span>
            <strong class="text-end">{{ $bilan->mesure('quantite_produit', 2) }} {{ $bilan->unite_mesure ?: 'kg' }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Mode de culture</span>
            <span class="badge bg-label-primary">{{ ucfirst(str_replace('_', ' ', $bilan->mode_culture ?: 'standard')) }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Type emballage</span>
            <span class="badge bg-label-warning text-end">{{ ucfirst($bilan->type_emballage ?: 'aucun') }} ({{ $bilan->mesure('poids_emballage_g', 0) }} g)</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Transport</span>
            <span class="text-end">{{ ucfirst($bilan->mode_transport ?: 'camion') }} • {{ $bilan->mesure('distance_km', 0) }} km</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Consommation d'eau</span>
            <strong>{{ $bilan->mesure('consommation_eau_l', 0) }} L</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <span class="text-muted">Impact transport</span>
            <strong>{{ $bilan->mesure('co2_transport') }} kg CO2e</strong>
          </li>
          <li class="list-group-item">
            <span class="text-muted d-block mb-1">Commentaire</span>
            <span>{{ $bilan->commentaires ?: 'Aucun commentaire' }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="bx bx-data me-1 text-info"></i> Coefficients Utilisés</h5>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush small">
          @if ($bilan->facteurEmission)
            <li class="list-group-item">
              <span class="text-muted d-block">Facteur principal :</span>
              <strong>{{ $bilan->facteurEmission->nom }}</strong>
              <div class="text-primary">{{ $bilan->facteurEmission->valeur }} {{ $bilan->facteurEmission->unite }}</div>
            </li>
          @endif
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
          @if (! $bilan->facteurEmission && ! $bilan->facteurProduction && ! $bilan->facteurTransport && ! $bilan->facteurEmballage)
            <li class="list-group-item text-muted">Aucun coefficient n'est rattaché à ce bilan.</li>
          @endif
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
