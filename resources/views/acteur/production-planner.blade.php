@extends('layouts.front')

@section('title', $pageTitle)

@section('content')
@include('partials.front.page-header', ['titre' => $pageTitle])

<div class="container py-5">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 p-lg-5">
            <span class="badge bg-primary-subtle text-primary mb-2">Prévision basée sur vos données</span>
            <h1 class="h3">{{ $pageTitle }}</h1>
            <p class="text-muted">Sélectionnez un produit de votre catalogue. Les estimations utilisent uniquement les commandes et les stocks réels du {{ $actorLabel }} connecté.</p>

            <form method="POST" action="{{ route('acteur.production-planner.predict') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label for="product_id" class="form-label fw-semibold">Produit</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Sélectionner un produit</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((int) old('product_id', $forecast['product_id'] ?? 0) === $product->id)>
                                {{ $product->name }} — {{ $product->type_produit === 'transforme' ? 'produit transformé' : 'matière première' }}
                            </option>
                        @endforeach
                    </select>
                    @if($products->isEmpty())
                        <div class="form-text text-warning">Aucun produit publié ne correspond à votre rôle.</div>
                    @endif
                </div>
                <div class="col-md-3">
                    <label for="horizon" class="form-label fw-semibold">Période</label>
                    <select name="horizon" id="horizon" class="form-select" required>
                        <option value="7" @selected((int) old('horizon', $horizon) === 7)>7 prochains jours</option>
                        <option value="30" @selected((int) old('horizon', $horizon) === 30)>30 prochains jours</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit" @disabled($products->isEmpty())>
                        <i class="fas fa-chart-line me-2"></i>Générer la prévision
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($forecast)
        @if($forecast['status'] === 'insufficient')
            <div class="alert alert-warning border-0 shadow-sm">
                <h2 class="h5">Prévision non générée</h2>
                <p class="mb-0">{{ $forecast['message'] }}</p>
                <small class="d-block mt-2">Les résultats ne sont pas inventés lorsque l’historique réel est insuffisant.</small>
            </div>
            @if($actorLabel === 'transformateur' && !empty($forecast['materials']['message']))
                <div class="alert alert-info">
                    <strong>Matières premières :</strong> {{ $forecast['materials']['message'] }}
                    @if(!empty($forecast['materials']['origins']))
                        <div class="mt-1">Provenance enregistrée : {{ implode(', ', $forecast['materials']['origins']) }}.</div>
                    @endif
                </div>
            @endif
        @elseif($forecast['status'] === 'unavailable')
            <div class="alert alert-danger border-0 shadow-sm">
                <h2 class="h5">Service de prévision indisponible</h2>
                <p class="mb-0">{{ $forecast['message'] }}</p>
            </div>
        @elseif($forecast['status'] === 'ok')
            @php
                $summary = $forecast['summary'];
                $trendLabel = ['hausse' => 'Hausse attendue', 'baisse' => 'Baisse attendue', 'stable' => 'Demande stable'][$summary['trend']] ?? 'Tendance inconnue';
                $trendClass = $summary['trend'] === 'hausse' ? 'warning' : ($summary['trend'] === 'baisse' ? 'info' : 'success');
            @endphp
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h4 mb-1">Prévision : {{ $forecast['product_name'] }}</h2>
                    <p class="text-muted mb-0">Estimation pour les {{ $forecast['horizon'] }} prochains jours.</p>
                </div>
                <span class="badge text-bg-{{ $trendClass }} px-3 py-2">{{ $trendLabel }}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card h-100 border-0 bg-light"><div class="card-body"><small class="text-muted">Stock disponible</small><div class="h4 mb-0">{{ number_format($forecast['stock'], 3, ',', ' ') }}</div></div></div></div>
                <div class="col-md-3"><div class="card h-100 border-0 bg-light"><div class="card-body"><small class="text-muted">Demande moyenne historique</small><div class="h4 mb-0">{{ number_format($summary['historical_average'], 3, ',', ' ') }}</div></div></div></div>
                <div class="col-md-3"><div class="card h-100 border-0 bg-light"><div class="card-body"><small class="text-muted">Demande estimée</small><div class="h4 mb-0">{{ number_format($summary['forecast_total'], 3, ',', ' ') }}</div></div></div></div>
                <div class="col-md-3"><div class="card h-100 border-0 bg-light"><div class="card-body"><small class="text-muted">Besoin supplémentaire</small><div class="h4 mb-0">{{ number_format($summary['stock_gap'], 3, ',', ' ') }}</div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body"><canvas id="forecast-chart" height="110"></canvas></div>
            </div>

            <div class="alert alert-{{ $summary['stock_gap'] > 0 ? 'warning' : 'success' }}">
                <strong>Recommandation :</strong>
                @if($summary['stock_gap'] > 0)
                    le stock actuel est inférieur à la demande estimée. Préparez au moins {{ number_format($summary['stock_gap'], 3, ',', ' ') }} unité(s) supplémentaire(s), selon l’unité enregistrée dans vos lots.
                @else
                    le stock disponible couvre la demande estimée sur la période analysée.
                @endif
            </div>

            @if($forecast['metrics'])
                <p class="small text-muted">Qualité du modèle — MAE : {{ number_format((float) ($forecast['metrics']['mae'] ?? 0), 3, ',', ' ') }}, RMSE : {{ number_format((float) ($forecast['metrics']['rmse'] ?? 0), 3, ',', ' ') }}. Ces valeurs mesurent l’erreur sur une partie historique séparée chronologiquement.</p>
            @endif

            @if($actorLabel === 'transformateur' && ($forecast['materials']['status'] ?? null) !== 'not_applicable')
                <div class="alert alert-info">
                    <strong>Matières premières :</strong> {{ $forecast['materials']['message'] }}
                    @if(!empty($forecast['materials']['origins']))
                        <div class="mt-1">Provenance enregistrée : {{ implode(', ', $forecast['materials']['origins']) }}.</div>
                    @endif
                </div>
            @endif
        @endif
    @endif
</div>
@endsection

@push('scripts')
@if($forecast && $forecast['status'] === 'ok')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const history = @json($forecast['history']);
        const predictions = @json($forecast['predictions']);
        new Chart(document.getElementById('forecast-chart'), {
            type: 'line',
            data: {
                labels: [...history.map(item => item.date), ...predictions.map(item => item.date)],
                datasets: [
                    { label: 'Commandes réelles', data: [...history.map(item => item.quantity), ...predictions.map(() => null)], borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.12)', tension: .3 },
                    { label: 'Prévision', data: [...history.map(() => null), ...predictions.map(item => item.quantity)], borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.12)', borderDash: [6, 4], tension: .3 }
                ]
            },
            options: { responsive: true, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true } } }
        });
    </script>
@endif
@endpush
