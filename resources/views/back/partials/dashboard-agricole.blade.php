<div class="card mb-6">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h5 class="mb-1">Activité agricole</h5>
                <p class="mb-0 text-body-secondary">La période filtre les produits créés et les récoltes prévues. Les stocks restent les quantités actuelles. La file de validation ignore la période.</p>
            </div>
            <span class="badge {{ $agricole['insights']['source'] === 'ia' ? 'bg-label-primary' : 'bg-label-secondary' }}">
                {{ $agricole['insights']['source'] === 'ia' ? 'Généré par IA' : 'Selon les règles' }}
            </span>
        </div>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3"><input class="form-control" type="date" name="du" value="{{ request('du') }}" aria-label="Du"></div>
            <div class="col-md-3"><input class="form-control" type="date" name="au" value="{{ request('au') }}" aria-label="Au"></div>
            <div class="col-md-2">
                <select class="form-select" name="produit" aria-label="Produit">
                    <option value="">Tous les produits</option>
                    @foreach ($agricole['options']['produits'] as $produit)
                        <option value="{{ $produit }}" @selected(request('produit') === $produit)>{{ $produit }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="region" aria-label="Région">
                    <option value="">Toutes les régions</option>
                    @foreach ($agricole['options']['regions'] as $region)
                        <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filtrer</button></div>
        </form>
        <div class="row g-3">
            @foreach ($agricole['kpi'] as $libelle => $valeur)
                <div class="col-6 col-xl">
                    <div class="border rounded p-3 h-100">
                        <div class="text-body-secondary">{{ $libelle }}</div>
                        <h4 class="mb-0">{{ $valeur }}</h4>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-6 mb-6">
    <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h5>Produits publiés par catégorie</h5><canvas id="graphique-categories"></canvas>@if ($agricole['graphiques']['categories'] === [])<p class="mt-3 mb-0 text-body-secondary">Aucun produit publié pour ces filtres.</p>@endif</div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h5>Stocks actuels par région (kg)</h5><canvas id="graphique-stocks-regions"></canvas>@if ($agricole['graphiques']['regions'] === [])<p class="mt-3 mb-0 text-body-secondary">Aucun stock suivi pour ces filtres.</p>@endif</div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h5>Récoltes prévues par région (kg)</h5><canvas id="graphique-recoltes-regions"></canvas>@if ($agricole['graphiques']['regionsRecoltes'] === [])<p class="mt-3 mb-0 text-body-secondary">Aucune récolte prévue pour ces filtres.</p>@endif</div></div></div>
</div>

<div class="row g-6 mb-6">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Produits à valider</h5>
                <a href="{{ route('admin.produits.validation') }}">Centre de validation</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Produit</th><th>Producteur</th><th>Analyse</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($agricole['attente'] as $produit)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.produits.revue', $produit) }}">{{ $produit->name }}</a>
                                    <small class="d-block text-body-secondary">{{ $produit->category->name ?? 'Sans catégorie' }}</small>
                                </td>
                                <td>{{ $produit->user->name }}<small class="d-block text-body-secondary">{{ $produit->user->ville ?: 'Région non renseignée' }}</small></td>
                                <td>
                                    @if (is_array($produit->ai_analysis) && filled($produit->ai_analysis['resume'] ?? null))
                                        {{ \Illuminate\Support\Str::limit($produit->ai_analysis['resume'], 120) }}
                                    @else
                                        <span class="text-body-secondary">Aucun rapport IA</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <form method="POST" action="{{ route('admin.produits.approuver', $produit) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success" type="submit">Approuver</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.produits.refuser', $produit) }}" class="mt-2">
                                        @csrf
                                        @method('PATCH')
                                        <input class="form-control form-control-sm mb-1" name="motif" minlength="10" maxlength="1000" placeholder="Motif du refus" required>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4">Aucun produit en attente pour ces filtres.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <h5 class="card-header">Alertes</h5>
            <div class="card-body">
                @forelse ($agricole['alertes'] as $alerte)
                    <div class="alert alert-{{ $alerte['niveau'] }}">
                        <strong>{{ $alerte['titre'] }}</strong>
                        <div>{{ $alerte['detail'] }}</div>
                    </div>
                @empty
                    <p class="mb-0 text-body-secondary">Aucune rupture, alerte météo ou anomalie pour ces filtres.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <h5>Lecture de l’activité</h5>
        <p class="mb-2">{{ $agricole['insights']['texte'] }}</p>
        @if ($agricole['insights']['priorites'] !== [])
            <ul class="mb-0">
                @foreach ($agricole['insights']['priorites'] as $priorite)
                    <li>{{ $priorite }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@push('scripts')
@php($graphiquesAgricoles = $agricole['graphiques'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const agricole = @json($graphiquesAgricoles);
if (agricole.categories.length) {
    new Chart(document.getElementById('graphique-categories'), {
        type: 'doughnut',
        data: { labels: agricole.categories, datasets: [{ data: agricole.produits, backgroundColor: ['#81c408', '#ffb524', '#0d6efd', '#ff6b6b', '#6f42c1'] }] }
    });
}
if (agricole.regions.length) {
    new Chart(document.getElementById('graphique-stocks-regions'), {
        type: 'bar',
        data: { labels: agricole.regions, datasets: [{ label: 'kg disponibles', data: agricole.stocks, backgroundColor: '#81c408' }] }
    });
}
if (agricole.regionsRecoltes.length) {
    new Chart(document.getElementById('graphique-recoltes-regions'), {
        type: 'bar',
        data: { labels: agricole.regionsRecoltes, datasets: [{ label: 'kg estimés', data: agricole.recoltes, backgroundColor: '#ffb524' }] }
    });
}
</script>
@endpush
