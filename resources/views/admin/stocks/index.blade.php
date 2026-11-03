@extends('layouts.back')

@section('title', 'Stocks agricoles')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Stocks agricoles</h4>
        <p class="mb-0 text-body-secondary">Disponibilités déclarées par les producteurs. Les lots ne sont pas additionnés aux récoltes ni aux commandes.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Fiches</div><div class="fs-4">{{ $stats['fiches'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Disponible</div><div class="fs-4">{{ \App\Models\Harvest::formaterKg($stats['kg']) }} kg</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">En rupture</div><div class="fs-4">{{ $stats['rupture'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Stock faible</div><div class="fs-4">{{ $stats['faible'] }}</div></div></div></div>
</div>

<form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
        <select class="form-select" name="produit">
            <option value="">Tous les produits</option>
            @foreach ($produits as $produit)
                <option value="{{ $produit }}" @selected(request('produit') === $produit)>{{ $produit }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="region">
            <option value="">Toutes les régions</option>
            @foreach ($regions as $region)
                <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="niveau">
            <option value="">Tous les niveaux</option>
            <option value="rupture" @selected(request('niveau') === 'rupture')>Rupture</option>
            <option value="faible" @selected(request('niveau') === 'faible')>Stock faible</option>
            <option value="ok" @selected(request('niveau') === 'ok')>Disponible</option>
        </select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Filtrer</button></div>
</form>

@if ($alertes !== [])
    <div class="mb-4">
        @foreach ($alertes as $alerte)
            <div class="alert alert-{{ $alerte['classe'] }}">
                <strong>{{ $alerte['titre'] }}</strong> — {{ $alerte['culture'] }}, {{ $alerte['region'] }}.
                {{ $alerte['detail'] }}
            </div>
        @endforeach
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Disponible et prévision à 7 jours</h5><canvas id="graphique-produits"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Prévision à 7 jours par région</h5><canvas id="graphique-regions"></canvas></div></div></div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Producteur</th>
                    <th>Région</th>
                    <th>Disponible</th>
                    <th>7 jours</th>
                    <th>14 jours</th>
                    <th>30 jours</th>
                    <th>Niveau</th>
                    <th>Rupture estimée</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['fiche']->culture_name }}</td>
                        <td>{{ $ligne['fiche']->user->name }}</td>
                        <td>{{ $ligne['region'] }}</td>
                        <td>{{ \App\Models\Harvest::formaterKg($ligne['disponible']) }} kg</td>
                        <td>{{ \App\Models\Harvest::formaterKg($ligne['j7']) }} kg</td>
                        <td>{{ \App\Models\Harvest::formaterKg($ligne['j14']) }} kg</td>
                        <td>{{ \App\Models\Harvest::formaterKg($ligne['j30']) }} kg</td>
                        <td>
                            @if ($ligne['niveau'] === 'rupture') Rupture
                            @elseif ($ligne['niveau'] === 'faible') Stock faible
                            @else Disponible
                            @endif
                        </td>
                        <td>{{ $ligne['rupture'] ? $ligne['rupture']->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4">Aucun stock ne correspond à ces filtres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const donnees = @json($graphiques);
if (donnees.produits.length) {
    new Chart(document.getElementById('graphique-produits'), {
        type: 'bar',
        data: {
            labels: donnees.produits,
            datasets: [
                { label: 'Disponible', data: donnees.disponible, backgroundColor: '#81c408' },
                { label: 'Dans 7 jours', data: donnees.j7, backgroundColor: '#ffb524' }
            ]
        }
    });
}
if (donnees.regions.length) {
    new Chart(document.getElementById('graphique-regions'), {
        type: 'bar',
        data: { labels: donnees.regions, datasets: [{ label: 'Dans 7 jours', data: donnees.j7Region, backgroundColor: '#0d6efd' }] }
    });
}
</script>
@endpush
