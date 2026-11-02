@extends('layouts.front')

@section('title', 'Tableau de bord')

@section('content')
@include('partials.front.page-header', ['titre' => 'Tableau de bord'])

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Bonjour {{ auth()->user()->name }}</h2>
            <p class="mb-0 text-muted">Vos produits, stocks, récoltes et la météo de votre exploitation.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary rounded-pill" href="{{ route('acteur.produits.create') }}"><i class="fas fa-plus me-1"></i>Ajouter un produit</a>
            <a class="btn btn-outline-primary rounded-pill" href="{{ route('acteur.recoltes.create') }}"><i class="fas fa-calendar-plus me-1"></i>Planifier une récolte</a>
            <a class="btn btn-outline-primary rounded-pill" href="{{ route('acteur.stocks.index') }}"><i class="fas fa-boxes me-1"></i>Gérer les stocks</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Produits', $tableau['kpi']['produits'], 'fa-apple-alt'],
            ['Stock disponible', \App\Models\Harvest::formaterKg($tableau['kpi']['stock_kg']).' kg', 'fa-warehouse'],
            ['Récoltes prévues', $tableau['kpi']['recoltes'], 'fa-seedling'],
            ['Ventes confirmées', $tableau['kpi']['ventes'], 'fa-shopping-basket'],
            ['Alertes actives', $tableau['kpi']['alertes'], 'fa-exclamation-triangle'],
        ] as [$libelle, $valeur, $icone])
            <div class="col-6 col-lg">
                <div class="bg-light rounded p-3 h-100">
                    <div class="text-muted"><i class="fas {{ $icone }} me-1"></i>{{ $libelle }}</div>
                    <div class="fs-4">{{ $valeur }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="bg-light rounded p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Évolution des stocks</h2>
                    <a href="{{ route('acteur.stocks.index') }}">Détail</a>
                </div>
                <p class="text-muted">{{ $tableau['message_stock'] }} Montant des ventes confirmées : {{ number_format($tableau['kpi']['ventes_dt'], 3, ',', ' ') }} DT. Récoltes prévues : {{ \App\Models\Harvest::formaterKg($tableau['kpi']['recoltes_kg']) }} kg.</p>
                @if ($tableau['suivi'])
                    <canvas id="graphique-stocks" height="130"></canvas>
                    @if ($tableau['ruptures'] !== [])
                        <ul class="mt-3 mb-0">
                            @foreach ($tableau['ruptures'] as $rupture)
                                <li>Rupture estimée pour {{ $rupture['culture'] }} le {{ $rupture['date'] }}.</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 mb-0">Aucune date de rupture estimée sur 30 jours.</p>
                    @endif
                @else
                    <p class="mb-0">Aucun stock suivi. Ouvrez une fiche pour voir l’évolution à 7, 14 et 30 jours.</p>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="bg-light rounded p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Prochaines récoltes</h2>
                    <a href="{{ route('acteur.recoltes.index') }}">Calendrier</a>
                </div>
                @forelse ($tableau['recoltes'] as $recolte)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <a href="{{ route('acteur.recoltes.show', $recolte) }}">{{ $recolte->nom() }}</a>
                            <div class="small text-muted">{{ $recolte->planned_harvest_at->format('d/m/Y') }} · {{ $recolte->libelleStatut() }}</div>
                        </div>
                        <strong>{{ \App\Models\Harvest::formaterKg($recolte->estimated_kg) }} kg</strong>
                    </div>
                @empty
                    <p class="mb-0">Aucune récolte planifiée. Les quantités affichées ici sont celles que vous déclarez.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="bg-light rounded p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Météo agricole</h2>
                    <a href="{{ route('acteur.meteo') }}">Prévisions</a>
                </div>
                @if ($tableau['meteo']['disponible'])
                    <p class="mb-2">{{ $tableau['meteo']['lieu'] }} · {{ $tableau['meteo']['libelle'] }}</p>
                    <div class="row g-2 mb-3">
                        <div class="col-4"><div class="border rounded p-2">{{ $tableau['meteo']['temperature'] }} °C</div></div>
                        <div class="col-4"><div class="border rounded p-2">{{ $tableau['meteo']['humidite'] }} %</div></div>
                        <div class="col-4"><div class="border rounded p-2">{{ $tableau['meteo']['vent'] }} km/h</div></div>
                    </div>
                    <canvas id="graphique-meteo" height="120"></canvas>
                @else
                    <p class="mb-0">{{ $tableau['meteo']['message'] }}</p>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="bg-light rounded p-4 h-100">
                <h2 class="h5">Alertes actives</h2>
                @forelse ($tableau['alertes'] as $alerte)
                    <div class="alert alert-{{ $alerte['niveau'] }} mb-2">
                        <strong>{{ $alerte['titre'] }}</strong>
                        <div>{{ $alerte['detail'] }}</div>
                    </div>
                @empty
                    <p class="mb-0">Aucune alerte de stock ou de météo.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white border rounded p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Aperçu de l’exploitation</h2>
            <span class="badge {{ $tableau['insights']['source'] === 'ia' ? 'bg-primary' : 'bg-secondary' }}">
                {{ $tableau['insights']['source'] === 'ia' ? 'Généré par IA' : 'Selon les règles' }}
            </span>
        </div>
        <p>{{ $tableau['insights']['texte'] }}</p>
        @if ($tableau['insights']['priorites'] !== [])
            <ul class="mb-0">
                @foreach ($tableau['insights']['priorites'] as $priorite)
                    <li>{{ $priorite }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@php($courbeStock = $tableau['courbe'])
@php($joursMeteo = $tableau['meteo']['jours'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const courbe = @json($courbeStock);
const meteo = @json($joursMeteo);
const stocks = document.getElementById('graphique-stocks');
if (stocks) {
    new Chart(stocks, {
        type: 'line',
        data: {
            labels: ['Stock actuel', '7 jours', '14 jours', '30 jours'],
            datasets: [{
                label: 'Kilogrammes',
                data: courbe,
                borderColor: '#81c408',
                backgroundColor: 'rgba(129, 196, 8, 0.15)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
}
const graphiqueMeteo = document.getElementById('graphique-meteo');
if (graphiqueMeteo && meteo.length) {
    new Chart(graphiqueMeteo, {
        type: 'bar',
        data: {
            labels: meteo.map(function (jour) { return jour.date.slice(5); }),
            datasets: [
                { label: 'Max °C', data: meteo.map(function (jour) { return jour.max; }), backgroundColor: '#ffb524' },
                { label: 'Pluie mm', data: meteo.map(function (jour) { return jour.pluie; }), backgroundColor: '#0d6efd' }
            ]
        }
    });
}
</script>
@endpush
