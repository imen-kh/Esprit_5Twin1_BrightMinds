@extends('layouts.front')

@section('title', 'Impact Environnemental & Éco-Score')

@section('content')
<!-- Header Page -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Impact Environnemental & Éco-Score</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item active text-white">Environnement</li>
    </ol>
</div>

<!-- Section Présentation & Simulateur IA -->
<div class="container-fluid py-5">
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <!-- Bandeau introductif -->
        <div class="row g-4 align-items-center mb-5">
            <div class="col-lg-8">
                <span class="text-primary text-uppercase fw-bold">Transparence Écologique NutriTrace</span>
                <h2 class="display-6 fw-bold mb-3">Mesurez l'empreinte carbone et privilégiez les circuits courts</h2>
                <p class="text-muted">
                    Grâce à nos facteurs d'émission standardisés et à notre intelligence environnementale, nous évaluons l'impact de chaque aliment du champ à l'assiette et vous recommandons les meilleures alternatives locales de saison en Tunisie.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="bg-light p-4 rounded border shadow-sm">
                    <h5 class="fw-bold mb-3">Échelle Éco-Score</h5>
                    <div class="d-flex justify-content-center gap-1 mb-2">
                        <span class="badge bg-success fs-5 px-3 py-2">A</span>
                        <span class="badge bg-info fs-5 px-3 py-2">B</span>
                        <span class="badge bg-warning text-dark fs-5 px-3 py-2">C</span>
                        <span class="badge bg-danger fs-5 px-3 py-2">D</span>
                        <span class="badge bg-dark fs-5 px-3 py-2">E</span>
                    </div>
                    <small class="text-muted">De très faible impact (A) à impact critique (E)</small>
                </div>
            </div>
        </div>

        <!-- Simulateur IA interactif pour le consommateur -->
        <div class="card border-primary shadow-sm mb-5">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="mb-0 text-white"><i class="fas fa-robot me-2"></i> Simulateur Éco-Score IA & Alternative Locale</h4>
                <span class="badge bg-light text-primary">ChatGPT · OpenAI</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    Saisissez un produit alimentaire pour simuler son empreinte carbone et découvrir immédiatement une alternative locale tunisienne plus écologique.
                </p>
                <form id="front-simu-form" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Produit alimentaire</label>
                        <input type="text" id="simu_nom" class="form-control" placeholder="Ex: Tomates cerises, Oranges, Harissa..." value="Tomates importées" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Origine géographique</label>
                        <input type="text" id="simu_origine" class="form-control" placeholder="Ex: Espagne, Cap Bon, Sousse..." value="Importé Espagne">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Distance de transport (km)</label>
                        <input type="number" id="simu_distance" class="form-control" value="1200" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mode de culture</label>
                        <select id="simu_culture" class="form-select">
                            <option value="plein_champ">Plein champ conventionnel</option>
                            <option value="bio">Agriculture biologique</option>
                            <option value="serre_chauffee" selected>Serre chauffée</option>
                            <option value="hydroponique">Hydroponique</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Emballage</label>
                        <select id="simu_emballage" class="form-select">
                            <option value="plastique" selected>Barquette plastique</option>
                            <option value="carton">Carton recyclé</option>
                            <option value="verre">Bocal en verre</option>
                            <option value="vrac">Vrac (0 déchet)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Transport</label>
                        <select id="simu_transport" class="form-select">
                            <option value="camion" selected>Camion routier</option>
                            <option value="bateau">Bateau / Maritime</option>
                            <option value="avion">Avion / Fret</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 py-2" id="simu_btn">
                            <i class="fas fa-leaf me-1"></i> Calculer l'Éco-Score
                        </button>
                    </div>
                </form>

                <!-- Résultat Simulateur IA -->
                <div id="simu-resultat" class="mt-4 p-4 bg-light rounded border d-none">
                    <div class="row align-items-center g-3">
                        <div class="col-md-3 text-center border-end">
                            <small class="text-muted d-block mb-1">Éco-Score calculé</small>
                            <span id="simu-badge" class="badge px-4 py-3 fs-3"></span>
                            <div class="mt-2 fw-bold" id="simu-note"></div>
                        </div>
                        <div class="col-md-3 text-center border-end">
                            <small class="text-muted d-block mb-1">Empreinte Carbone</small>
                            <h3 class="fw-bold text-primary mb-0" id="simu-co2"></h3>
                            <small class="text-muted">kg CO2e / kg</small>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-success">
                                <h6 class="text-success fw-bold mb-1"><i class="fas fa-lightbulb me-1"></i> Alternative locale suggérée par l'IA :</h6>
                                <p class="mb-0 text-dark small" id="simu-alternative"></p>
                                <p class="mb-0 text-muted small mt-2" id="simu-source"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catalogue des Bilans Environnementaux Validés -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Classement des bilans</h3>
                <p class="text-muted mb-0">Du meilleur score au plus faible. Le filtre circuit court garde les produits à 150 km ou moins.</p>
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('front.environnement.create') }}" class="btn btn-primary mt-2">Ajouter un bilan environnemental</a>
                @endif
            </div>
            <!-- Filtre par Éco-Score -->
            <form method="GET" action="{{ route('front.environnement.index') }}" class="d-flex flex-wrap gap-2">
                @if (request('eco_score'))
                    <input type="hidden" name="eco_score" value="{{ request('eco_score') }}">
                @endif
                @if (request('circuit'))
                    <input type="hidden" name="circuit" value="1">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher un produit" style="min-width: 220px;">
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
            <div class="d-flex flex-wrap gap-2" role="group">
                <a href="{{ route('front.environnement.index', array_filter(['q' => request('q'), 'circuit' => request('circuit')])) }}" class="btn btn-outline-secondary {{ !request('eco_score') ? 'active' : '' }}">Tous</a>
                <a href="{{ route('front.environnement.index', array_filter(['eco_score' => 'A', 'q' => request('q'), 'circuit' => request('circuit')])) }}" class="btn btn-outline-success {{ request('eco_score') === 'A' ? 'active' : '' }}">Score A</a>
                <a href="{{ route('front.environnement.index', array_filter(['eco_score' => 'B', 'q' => request('q'), 'circuit' => request('circuit')])) }}" class="btn btn-outline-info {{ request('eco_score') === 'B' ? 'active' : '' }}">Score B</a>
                <a href="{{ route('front.environnement.index', array_filter(['eco_score' => 'C', 'q' => request('q'), 'circuit' => request('circuit')])) }}" class="btn btn-outline-warning {{ request('eco_score') === 'C' ? 'active' : '' }}">Score C</a>
                <a href="{{ route('front.environnement.index', array_filter(['eco_score' => 'D', 'q' => request('q'), 'circuit' => request('circuit')])) }}" class="btn btn-outline-danger {{ request('eco_score') === 'D' ? 'active' : '' }}">Score D/E</a>
                <a href="{{ route('front.environnement.index', array_filter(['eco_score' => request('eco_score'), 'q' => request('q'), 'circuit' => request('circuit') ? null : 1])) }}" class="btn btn-outline-success {{ request('circuit') ? 'active' : '' }}">Circuit court</a>
                <a href="{{ route('front.environnement.comparer') }}" class="btn btn-primary">Comparer deux produits</a>
            </div>
        </div>

        <!-- Grille des Bilans -->
        <div class="row g-4">
            @forelse ($bilans as $bilan)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border rounded-3 overflow-hidden">
                        <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center border-bottom">
                            <span class="badge {{ $bilan->ecoScoreBadgeClass() }} fs-6 px-3 py-1">
                                #{{ $bilans->firstItem() + $loop->index }} · Éco-Score {{ $bilan->eco_score }}
                            </span>
                            <span class="text-muted small"><strong>{{ $bilan->score_numerique }}</strong>/100</span>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold text-dark mb-2">{{ $bilan->nomAffiche() }}</h5>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $bilan->origine_geographique ?: 'Tunisie' }}
                                • {{ $bilan->mesure('distance_km', 0) }} km ({{ ucfirst($bilan->mode_transport ?: 'camion') }})
                            </p>
                            <p class="small mb-3">
                                <span class="badge {{ $bilan->scoreBadgeClass() }}">{{ $bilan->scoreAppreciation() }}</span>
                                @if ($bilan->facteurEmission)
                                    <span class="text-muted ms-1">{{ $bilan->facteurEmission->nom }}</span>
                                @endif
                            </p>

                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                                <span class="small text-muted">Émission CO2 :</span>
                                <strong class="text-primary">{{ $bilan->mesure('co2_total') }} kg CO2e</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-3">
                                <span class="small text-muted">Eau / transport :</span>
                                <strong>{{ $bilan->mesure('consommation_eau_l', 0) }} L · {{ $bilan->mesure('co2_transport') }} kg</strong>
                            </div>

                            @if ($bilan->alternative_locale)
                                <div class="p-2 bg-white rounded border border-success small mb-3">
                                    <strong class="text-success d-block mb-1"><i class="fas fa-seedling me-1"></i> Alternative locale :</strong>
                                    <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                        {{ \Illuminate\Support\Str::limit($bilan->alternative_locale, 120) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-top p-3 d-flex justify-content-end flex-wrap gap-2">
                            <a href="{{ route('front.environnement.show', $bilan) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fas fa-info-circle me-1"></i> Voir
                            </a>
                            @if (auth()->user()?->isAdmin())
                                <a href="{{ route('front.environnement.edit', $bilan) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Modifier</a>
                                <form action="{{ route('front.environnement.destroy', $bilan) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bilan environnemental ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Aucun bilan environnemental ne correspond à ce filtre.</p>
                </div>
            @endforelse
        </div>

        @if ($bilans->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $bilans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('front-simu-form');
    const btn = document.getElementById('simu_btn');
    const resultBox = document.getElementById('simu-resultat');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calcul en cours...';

        const payload = {
            nom_produit: document.getElementById('simu_nom').value,
            origine_geographique: document.getElementById('simu_origine').value,
            distance_km: document.getElementById('simu_distance').value,
            mode_culture: document.getElementById('simu_culture').value,
            type_emballage: document.getElementById('simu_emballage').value,
            mode_transport: document.getElementById('simu_transport').value,
            poids_emballage_g: 30,
            quantite_produit: 1.0,
        };

        try {
            const resp = await fetch("{{ route('front.environnement.simuler') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-leaf me-1"></i> Calculer l\'Éco-Score';

            if (data.success && data.data) {
                const res = data.data;
                resultBox.classList.remove('d-none');
                const badgeColors = { 'A': 'bg-success', 'B': 'bg-info', 'C': 'bg-warning text-dark', 'D': 'bg-danger', 'E': 'bg-dark' };
                const badge = document.getElementById('simu-badge');
                badge.className = `badge px-4 py-3 fs-3 ${badgeColors[res.eco_score] || 'bg-primary'}`;
                badge.textContent = `Grade ${res.eco_score}`;

                document.getElementById('simu-note').textContent = `Score : ${res.score_numerique} / 100`;
                document.getElementById('simu-co2').textContent = res.co2_total;
                document.getElementById('simu-alternative').textContent = res.alternative_locale;
                const sources = {
                    anthropic: 'Texte rédigé par Claude (Anthropic). Le score et le CO₂ viennent des facteurs d\'émission.',
                    bilan: 'Texte déjà enregistré pour ce produit. Claude n\'a pas été rappelé.',
                    anthropic_indisponible: 'Claude n\'a pas répondu. La formule locale a été utilisée.'
                };
                document.getElementById('simu-source').textContent = sources[res.source_ia]
                    || 'Formule locale utilisée. Configurez ANTHROPIC_API_KEY dans le fichier .env pour activer Claude.';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-leaf me-1"></i> Calculer l\'Éco-Score';
            alert('Erreur lors de la simulation.');
            console.error(err);
        }
    });
});
</script>
@endpush
