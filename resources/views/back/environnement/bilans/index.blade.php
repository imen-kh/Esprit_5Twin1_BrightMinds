@extends('layouts.back')

@section('title', 'Bilans Environnementaux')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Bilans Environnementaux & Éco-Score</h4>
    <p class="text-muted mb-0">Évaluation de l'empreinte carbone produit, attribution de l'Éco-Score et suggestions d'alternatives locales par IA.</p>
  </div>
  <a href="{{ route('admin.bilans-environnementaux.create') }}" class="btn btn-primary">
    <i class="bx bx-plus me-1"></i> Nouveau bilan
  </a>
</div>

<!-- Cartes Statistiques & KPIs -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small">Total Bilans</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $stats['total'] }}</h4>
            </div>
            <small class="text-success">Produits évalués</small>
          </div>
          <span class="badge bg-label-primary rounded p-2">
            <i class="bx bx-leaf bx-sm"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small">Éco-Score Moyen</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $stats['score_moyen'] }} <span class="fs-6 text-muted">/100</span></h4>
            </div>
            <small class="text-info">Indice de durabilité</small>
          </div>
          <span class="badge bg-label-info rounded p-2">
            <i class="bx bx-tachometer bx-sm"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small">CO2 Moyen / Produit</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $stats['co2_moyen'] }} <span class="fs-6 text-muted">kg</span></h4>
            </div>
            <small class="text-muted">Émissions directes & indirectes</small>
          </div>
          <span class="badge bg-label-warning rounded p-2">
            <i class="bx bx-cloud bx-sm"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <span class="text-muted small">Distribution Éco-Scores</span>
        <div class="d-flex gap-1 mt-2">
          <span class="badge bg-success" title="Score A : {{ $stats['nb_a'] }}">A ({{ $stats['nb_a'] }})</span>
          <span class="badge bg-info" title="Score B : {{ $stats['nb_b'] }}">B ({{ $stats['nb_b'] }})</span>
          <span class="badge bg-warning text-dark" title="Score C : {{ $stats['nb_c'] }}">C ({{ $stats['nb_c'] }})</span>
          <span class="badge bg-danger" title="Score D : {{ $stats['nb_d'] }}">D ({{ $stats['nb_d'] }})</span>
          <span class="badge bg-dark" title="Score E : {{ $stats['nb_e'] }}">E ({{ $stats['nb_e'] }})</span>
        </div>
        <small class="text-muted d-block mt-1">Norme de notation ADEME</small>
      </div>
    </div>
  </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.bilans-environnementaux.index') }}" class="row g-3 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher par nom produit, origine ou alternative...">
        </div>
      </div>
      <div class="col-md-3">
        <select name="eco_score" class="form-select" onchange="this.form.submit()">
          <option value="">Tous les Éco-Scores</option>
          <option value="A" @selected(request('eco_score') === 'A')>Éco-Score A (Excellente durabilité)</option>
          <option value="B" @selected(request('eco_score') === 'B')>Éco-Score B (Bonne durabilité)</option>
          <option value="C" @selected(request('eco_score') === 'C')>Éco-Score C (Impact modéré)</option>
          <option value="D" @selected(request('eco_score') === 'D')>Éco-Score D (Impact élevé)</option>
          <option value="E" @selected(request('eco_score') === 'E')>Éco-Score E (Critique)</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="statut" class="form-select" onchange="this.form.submit()">
          <option value="">Tous statuts</option>
          <option value="valide" @selected(request('statut') === 'valide')>Validé</option>
          <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
          <option value="archive" @selected(request('statut') === 'archive')>Archivé</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
        @if(request()->hasAny(['q', 'eco_score', 'statut']))
          <a href="{{ route('admin.bilans-environnementaux.index') }}" class="btn btn-outline-secondary">Effacer</a>
        @endif
      </div>
    </form>
  </div>
</div>

<!-- Table des Bilans -->
<div class="card">
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Produit évalué</th>
          <th>Éco-Score</th>
          <th>CO2 Total</th>
          <th>Décomposition (Prod / Transp / Emb)</th>
          <th>Distance</th>
          <th>Alternative Locale (IA)</th>
          <th>Statut</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse ($bilans as $bilan)
          <tr>
            <td>
              <strong>{{ $bilan->nom_produit }}</strong>
              <div class="small text-muted">
                {{ $bilan->quantite_produit }} {{ $bilan->unite_mesure }} • {{ $bilan->origine_geographique ?: 'Non précisé' }}
              </div>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $bilan->ecoScoreBadgeClass() }} fs-6 px-3 py-1 fw-bold">
                  {{ $bilan->eco_score }}
                </span>
                <small class="text-muted fw-semibold">{{ $bilan->score_numerique }}/100</small>
              </div>
            </td>
            <td>
              <strong class="text-primary">{{ number_format($bilan->co2_total, 3, ',', ' ') }}</strong>
              <small class="text-muted d-block">kg CO2e</small>
            </td>
            <td>
              <div class="small">
                <span title="Production"><i class="bx bx-leaf text-success"></i> {{ number_format($bilan->co2_production, 2) }}</span> |
                <span title="Transport"><i class="bx bx-car text-info"></i> {{ number_format($bilan->co2_transport, 2) }}</span> |
                <span title="Emballage"><i class="bx bx-box text-warning"></i> {{ number_format($bilan->co2_emballage, 2) }}</span>
              </div>
            </td>
            <td>
              <span>{{ number_format($bilan->distance_km, 0, ',', ' ') }} km</span>
              <small class="text-muted d-block">{{ ucfirst($bilan->mode_transport ?: 'camion') }}</small>
            </td>
            <td>
              @if ($bilan->alternative_locale)
                <span class="badge bg-label-success" title="{{ $bilan->alternative_locale }}">
                  <i class="bx bx-bot me-1"></i>
                  @if($bilan->reduction_co2_estimee_pourcent)
                    -{{ $bilan->reduction_co2_estimee_pourcent }}% CO2
                  @else
                    Alternative IA
                  @endif
                </span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              @if ($bilan->statut === 'valide')
                <span class="badge bg-label-success">Validé</span>
              @elseif ($bilan->statut === 'en_attente')
                <span class="badge bg-label-warning">En attente</span>
              @else
                <span class="badge bg-label-secondary">Archivé</span>
              @endif
            </td>
            <td class="text-end">
              <a href="{{ route('admin.bilans-environnementaux.show', $bilan) }}" class="btn btn-sm btn-outline-info" title="Détails du bilan">
                <i class="bx bx-show"></i>
              </a>
              <a href="{{ route('admin.bilans-environnementaux.edit', $bilan) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                <i class="bx bx-edit-alt"></i>
              </a>
              <form action="{{ route('admin.bilans-environnementaux.destroy', $bilan) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce bilan environnemental ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                  <i class="bx bx-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center py-5">
              <p class="text-muted mb-0">Aucun bilan environnemental enregistré pour le moment.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if ($bilans->hasPages())
    <div class="card-footer">
      {{ $bilans->links() }}
    </div>
  @endif
</div>
@endsection
