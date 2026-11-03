@extends('layouts.back')

@section('title', 'Récoltes')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Récoltes prévues</h4>
        <p class="mb-0 text-body-secondary">Quantités estimées déclarées par les producteurs, distinctes des quantités réellement récoltées.</p>
    </div>
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
    <div class="col-md-2"><input class="form-control" type="date" name="du" value="{{ request('du') }}"></div>
    <div class="col-md-2"><input class="form-control" type="date" name="au" value="{{ request('au') }}"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filtrer</button></div>
</form>

<div class="row g-4 mb-4">
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Par produit (kg)</h5><canvas id="graphique-produits"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Par région (kg estimés)</h5><canvas id="graphique-regions"></canvas></div></div></div>
    <div class="col-12"><div class="card"><div class="card-body"><h5>Par période (kg estimés)</h5><canvas id="graphique-mois" height="80"></canvas></div></div></div>
</div>

<div class="card"><div class="table-responsive"><table class="table">
    <thead>
        <tr><th>Produit</th><th>Producteur</th><th>Région</th><th>Période</th><th>Estimée</th><th>Récoltée</th><th>Statut</th><th>Alerte</th></tr>
    </thead>
    <tbody>
    @forelse ($recoltes as $recolte)
        @php $risque = $alertes[$recolte->id] ?? null; @endphp
        <tr>
            <td>{{ $recolte->nom() }}</td>
            <td>{{ $recolte->user->name }}</td>
            <td>{{ $recolte->user->ville ?: 'Non renseignée' }}</td>
            <td>{{ $recolte->planned_harvest_at->format('d/m/Y') }}</td>
            <td>{{ \App\Models\Harvest::formaterKg($recolte->estimated_kg) }} kg</td>
            <td>{{ $recolte->harvested_kg === null ? '—' : \App\Models\Harvest::formaterKg($recolte->harvested_kg).' kg' }}</td>
            <td><span class="badge {{ $recolte->classeStatut() }}">{{ $recolte->libelleStatut() }}</span></td>
            <td>
                @if (! $risque)
                    Hors prévision à 7 jours
                @elseif ($risque['alertes'] !== [])
                    {{ collect($risque['alertes'])->pluck('titre')->implode(', ') }}
                @else
                    {{ $risque['message'] ?: 'Aucune alerte' }}
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="8">Aucune récolte sur cette période.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const donnees = @json($graphiques);
const couleurs = ['#81c408', '#ffb524', '#0d6efd'];
if (donnees.produits.length) {
    new Chart(document.getElementById('graphique-produits'), {
        type: 'bar',
        data: {
            labels: donnees.produits,
            datasets: [
                { label: 'Estimée', data: donnees.estimeProduit, backgroundColor: couleurs[0] },
                { label: 'Récoltée', data: donnees.recolteProduit, backgroundColor: couleurs[1] }
            ]
        }
    });
}
if (donnees.regions.length) {
    new Chart(document.getElementById('graphique-regions'), {
        type: 'bar',
        data: { labels: donnees.regions, datasets: [{ label: 'Estimée', data: donnees.estimeRegion, backgroundColor: couleurs[2] }] }
    });
}
if (donnees.mois.length) {
    new Chart(document.getElementById('graphique-mois'), {
        type: 'line',
        data: { labels: donnees.mois, datasets: [{ label: 'Estimée', data: donnees.estimeMois, borderColor: couleurs[0], tension: 0.3 }] }
    });
}
</script>
@endpush
