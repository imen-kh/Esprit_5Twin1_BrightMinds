@extends('layouts.front')

@section('title', 'Météo agricole')

@section('content')
@include('partials.front.page-header', ['titre' => 'Météo agricole'])

<div class="container py-5">
    <form method="GET" action="{{ route('acteur.meteo') }}" class="bg-light rounded p-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="ville" class="form-label">Voir les prévisions d’une autre ville</label>
                <input
                    type="text"
                    class="form-control @error('ville') is-invalid @enderror"
                    id="ville"
                    name="ville"
                    value="{{ old('ville', $tableau['ville_demandee']) }}"
                    placeholder="Kasserine, Gafsa, Sfax…"
                    maxlength="80">
                @error('ville')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Afficher</button>
            </div>
            @if ($tableau['ville_demandee'])
                <div class="col-auto">
                    <a href="{{ route('acteur.meteo') }}" class="btn btn-outline-secondary">Ma localisation</a>
                </div>
            @endif
        </div>
        @if ($tableau['ville_demandee'])
            <p class="small text-muted mb-0 mt-3">Consultation ponctuelle. Votre profil n’est pas modifié.</p>
        @endif
    </form>

    @if (! $tableau['disponible'])
        <div class="alert alert-warning">{{ $tableau['message'] }}</div>
    @else
        @php
            $actuelle = $tableau['meteo']['actuelle'];
            $jours = $tableau['meteo']['jours'];
        @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="h3 text-primary mb-1">{{ $tableau['lieu']['nom'] }}</h2>
                <p class="mb-0 text-muted">{{ $actuelle['libelle'] }} · relevé Open-Meteo</p>
            </div>
            @if ($tableau['cultures'] !== [])
                <p class="mb-0">Cultures suivies : {{ implode(', ', $tableau['cultures']) }}</p>
            @endif
        </div>

        <div class="row g-4 mb-4">
            @foreach ([
                ['Température', $actuelle['temperature'].' °C', 'fa-thermometer-half'],
                ['Humidité', $actuelle['humidite'].' %', 'fa-tint'],
                ['Précipitations', $actuelle['precipitations'].' mm', 'fa-cloud-rain'],
                ['Vent', $actuelle['vent'].' km/h', 'fa-wind'],
            ] as [$libelle, $valeur, $icone])
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4 h-100">
                        <i class="fas {{ $icone }} fa-lg text-primary mb-3"></i>
                        <p class="mb-1 text-muted">{{ $libelle }}</p>
                        <h3 class="h4 mb-0">{{ $valeur }}</h3>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="border rounded p-3 p-md-4 h-100">
                    <h2 class="h5 mb-3">Prévisions sur 7 jours</h2>
                    <canvas id="graphique-meteo" height="140" aria-label="Températures et pluie sur 7 jours"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr><th>Jour</th><th>Conditions</th><th>Min</th><th>Max</th><th>Pluie</th><th>Vent</th></tr>
                            </thead>
                            <tbody>
                            @foreach ($jours as $jour)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($jour['date'])->format('d/m') }}</td>
                                    <td>{{ $jour['libelle'] }}</td>
                                    <td>{{ $jour['min'] }} °C</td>
                                    <td>{{ $jour['max'] }} °C</td>
                                    <td>{{ $jour['pluie'] }} mm</td>
                                    <td>{{ $jour['vent'] }} km/h</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="border rounded p-3 p-md-4 mb-4">
                    <h2 class="h5 mb-3">Alertes</h2>
                    @forelse ($tableau['alertes'] as $alerte)
                        <div class="alert alert-{{ $alerte['classe'] }} mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $alerte['titre'] }}</strong>
                                <span class="badge bg-{{ $alerte['classe'] }}">{{ $alerte['niveau'] === 'eleve' ? 'Élevé' : 'Modéré' }}</span>
                            </div>
                            <p class="mb-1">{{ $alerte['detail'] }}</p>
                            <p class="mb-0">{{ $alerte['recommandation'] }}</p>
                        </div>
                    @empty
                        <p class="mb-0 text-muted">Aucune alerte pour les seuils de vos cultures sur les 7 prochains jours.</p>
                    @endforelse
                    <p class="small text-muted mt-3 mb-0">
                        Seuils : pluie {{ $tableau['seuils']['pluie_mm'] }} mm, chaleur {{ $tableau['seuils']['chaleur_c'] }} °C,
                        gel {{ $tableau['seuils']['gel_c'] }} °C, vent {{ $tableau['seuils']['vent_kmh'] }} km/h.
                    </p>
                </div>
                <div class="bg-light rounded p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Mes conseils agricoles</h2>
                        <span class="badge {{ $tableau['conseils_source'] === 'ia' ? 'bg-primary' : 'bg-secondary' }}">
                            {{ $tableau['conseils_source'] === 'ia' ? 'Généré par IA' : 'Selon les seuils' }}
                        </span>
                    </div>
                    <ul class="mb-0">
                        @foreach ($tableau['conseils'] as $conseil)
                            <li class="mb-2">{{ $conseil }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if ($tableau['disponible'])
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const jours = @json($tableau['meteo']['jours']);
new Chart(document.getElementById('graphique-meteo'), {
    data: {
        labels: jours.map(function (jour) { return jour.date.slice(5); }),
        datasets: [
            {
                type: 'line',
                label: 'Max °C',
                data: jours.map(function (jour) { return jour.max; }),
                borderColor: '#81c408',
                backgroundColor: '#81c408',
                tension: 0.3
            },
            {
                type: 'line',
                label: 'Min °C',
                data: jours.map(function (jour) { return jour.min; }),
                borderColor: '#ffb524',
                backgroundColor: '#ffb524',
                tension: 0.3
            },
            {
                type: 'bar',
                label: 'Pluie mm',
                data: jours.map(function (jour) { return jour.pluie; }),
                backgroundColor: 'rgba(13, 110, 253, 0.35)',
                yAxisID: 'pluie'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            pluie: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
        }
    }
});
</script>
@endpush
@endif
