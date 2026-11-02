@extends('layouts.front')

@section('title', $fiche->culture_name)

@section('content')
@include('partials.front.page-header', ['titre' => $fiche->culture_name])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <a href="{{ route('acteur.stocks.index') }}">Retour aux stocks</a>
        @if ($fiche->niveau() === 'rupture')
            <span class="badge bg-danger">Rupture</span>
        @elseif ($fiche->niveau() === 'faible')
            <span class="badge bg-warning text-dark">Stock faible</span>
        @else
            <span class="badge bg-success">Disponible</span>
        @endif
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="bg-light rounded p-3 h-100"><div class="text-muted">Disponible</div><div class="fs-4">{{ \App\Models\Harvest::formaterKg($prevision['disponible']) }} kg</div></div></div>
        <div class="col-md-3"><div class="bg-light rounded p-3 h-100"><div class="text-muted">Dans 7 jours</div><div class="fs-4">{{ \App\Models\Harvest::formaterKg($prevision['j7']) }} kg</div></div></div>
        <div class="col-md-3"><div class="bg-light rounded p-3 h-100"><div class="text-muted">Dans 14 jours</div><div class="fs-4">{{ \App\Models\Harvest::formaterKg($prevision['j14']) }} kg</div></div></div>
        <div class="col-md-3"><div class="bg-light rounded p-3 h-100"><div class="text-muted">Dans 30 jours</div><div class="fs-4">{{ \App\Models\Harvest::formaterKg($prevision['j30']) }} kg</div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="bg-light rounded p-4 h-100">
                <h2 class="h5">Évolution prévue</h2>
                <p class="text-muted">{{ $prevision['message'] }}</p>
                <canvas id="graphique-stock" height="120"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="bg-light rounded p-4 h-100">
                <h2 class="h5">Recommandations</h2>
                @if ($prevision['rupture'])
                    <p>Rupture estimée le <strong>{{ $prevision['rupture']->format('d/m/Y') }}</strong>.</p>
                @endif
                <ul class="mb-0">
                    @forelse ($prevision['recommandations'] as $texte)
                        <li>{{ $texte }}</li>
                    @empty
                        <li>Aucune action particulière au vu du stock et des sorties connues.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @if ($suspens['sans_poids'] > 0)
        <div class="alert alert-warning">{{ $suspens['sans_poids'] }} commande(s) confirmée(s) ne sont pas déduites. Indiquez le poids d’une unité vendue : une commande compte des pièces, pas des kilogrammes.</div>
    @endif
    @if ($suspens['sans_stock'] > 0)
        <div class="alert alert-warning">{{ $suspens['sans_stock'] }} vente(s) confirmée(s) dépassent la quantité disponible. Elles ne sont pas déduites, pour éviter un stock négatif.</div>
    @endif

    @if ($prevision['alerte_message'])
        <div class="alert alert-warning">{{ $prevision['alerte_message'] }}</div>
    @endif
    @foreach ($prevision['alertes'] as $alerte)
        <div class="alert alert-{{ $alerte['classe'] }}">
            <strong>{{ $alerte['titre'] }}</strong>
            <p class="mb-1">{{ $alerte['detail'] }}</p>
            <p class="mb-0">{{ $alerte['recommandation'] }}</p>
        </div>
    @endforeach

    <div class="row g-4">
        <div class="col-lg-4">
            <form method="POST" action="{{ route('acteur.stocks.mouvements.store', $fiche) }}" class="bg-light rounded p-4 mb-4">
                @csrf
                <h2 class="h5">Mouvement</h2>
                <div class="mb-3">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="entree" @selected(old('type') === 'entree')>Entrée (récolte ou apport)</option>
                        <option value="sortie" @selected(old('type') === 'sortie')>Sortie (vente)</option>
                        <option value="perte" @selected(old('type') === 'perte')>Perte</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="quantite_kg">Quantité (kg)</label>
                    <input class="form-control @error('quantite_kg') is-invalid @enderror" type="number" step="0.1" min="0.1" id="quantite_kg" name="quantite_kg" value="{{ old('quantite_kg') }}" required>
                    @error('quantite_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="moved_at">Date</label>
                    <input class="form-control @error('moved_at') is-invalid @enderror" type="date" id="moved_at" name="moved_at" value="{{ old('moved_at', now()->toDateString()) }}" required>
                    @error('moved_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="motif">Motif</label>
                    <input class="form-control @error('motif') is-invalid @enderror" id="motif" name="motif" value="{{ old('motif') }}" maxlength="255">
                    @error('motif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary rounded-pill px-4" type="submit">Enregistrer</button>
            </form>

            <form method="POST" action="{{ route('acteur.stocks.update', $fiche) }}" class="bg-light rounded p-4">
                @csrf
                @method('PATCH')
                <h2 class="h5">Seuil et ventes</h2>
                <div class="mb-3">
                    <label class="form-label" for="seuil_kg">Seuil d’alerte (kg)</label>
                    <input class="form-control" type="number" step="0.1" min="0" id="seuil_kg" name="seuil_kg" value="{{ old('seuil_kg', $fiche->seuil_kg) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="kg_par_unite">Poids d’une unité vendue (kg)</label>
                    <input class="form-control" type="number" step="0.001" min="0.001" id="kg_par_unite" name="kg_par_unite" value="{{ old('kg_par_unite', $fiche->kg_par_unite) }}">
                </div>
                <button class="btn btn-outline-primary rounded-pill px-4" type="submit">Mettre à jour</button>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="table-responsive bg-light rounded">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Quantité</th><th>Origine</th><th>Motif</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($mouvements as $mouvement)
                            <tr>
                                <td>{{ $mouvement->moved_at->format('d/m/Y') }}</td>
                                <td>{{ $mouvement->libelleType() }}</td>
                                <td>{{ \App\Models\Harvest::formaterKg($mouvement->quantite_kg) }} kg</td>
                                <td>{{ $mouvement->libelleSource() }}</td>
                                <td>{{ $mouvement->motif ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4">Aucun mouvement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php($courbe = [(float) $prevision['disponible'], (float) $prevision['j7'], (float) $prevision['j14'], (float) $prevision['j30']])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const courbe = @json($courbe);
new Chart(document.getElementById('graphique-stock'), {
    type: 'line',
    data: {
        labels: ['Stock actuel', '7 jours', '14 jours', '30 jours'],
        datasets: [{
            label: 'Stock (kg)',
            data: courbe,
            borderColor: '#81c408',
            backgroundColor: 'rgba(129, 196, 8, 0.15)',
            tension: 0.3,
            fill: true
        }]
    },
    options: { scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
