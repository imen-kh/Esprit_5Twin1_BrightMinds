@extends('layouts.back')

@section('title', 'Alertes qualité')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">Alertes qualité</h4>
        <p class="text-muted mb-0">
            Un produit visé par au moins {{ config('reclamations.alerte_seuil') }} réclamations ou avis ≤ 2★ en {{ config('reclamations.alerte_fenetre_jours') }} jours déclenche une alerte.
            DeepSeek dit s'il s'agit du même problème et conseille, ou non, un rappel du lot.
        </p>
    </div>
</div>

<ul class="nav nav-pills mb-4">
    @foreach (\App\Models\AlerteQualite::STATUTS as $valeur => $info)
        <li class="nav-item">
            <a class="nav-link {{ $statut === $valeur ? 'active' : '' }}" href="{{ route('admin.alertes.index', ['statut' => $valeur]) }}">
                {{ $info['label'] }} <span class="badge bg-label-secondary ms-1">{{ $compteurs[$valeur] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>

@forelse ($alertes as $alerte)
    <div class="card mb-4 border-start border-4 border-{{ $alerte->gravite === 'elevee' ? 'danger' : ($alerte->gravite === 'moyenne' ? 'warning' : 'info') }}">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <div>
                    <h5 class="mb-1">{{ $alerte->titre }}</h5>
                    <div class="small text-muted">
                        {{ $alerte->produit?->name }} · {{ $alerte->nb_signaux }} signalements · mise à jour {{ $alerte->updated_at->diffForHumans() }}
                        @if ($alerte->lot) · lot le plus cité : <strong>{{ $alerte->lot->code }}</strong>@endif
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge {{ $alerte->graviteBadge() }}">Gravité {{ mb_strtolower($alerte->graviteLibelle()) }}</span>
                    <span class="badge {{ $alerte->statutBadge() }}">{{ $alerte->statutLibelle() }}</span>
                    @if ($alerte->analyse_ia)<span class="ra-badge-ia ms-1"><i class="bx bx-bot"></i>Analysée par DeepSeek</span>@endif
                </div>
            </div>

            <p class="mb-2">{{ $alerte->resume }}</p>
            @if ($alerte->recommandation)
                <p class="mb-3 small"><i class="bx bx-bulb text-warning me-1"></i><strong>Recommandation :</strong> {{ $alerte->recommandation }}</p>
            @endif

            <div class="row g-3">
                <div class="col-lg-7">
                    <details>
                        <summary class="small fw-semibold">Voir les {{ $alerte->nb_signaux }} signalements</summary>
                        <ul class="list-unstyled small mt-2 mb-0">
                            @foreach ($alerte->reclamations() as $reclamation)
                                <li class="py-1 border-bottom">
                                    <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="ra-reference">{{ $reclamation->reference }}</a>
                                    — {{ $reclamation->sujet }} <span class="text-muted">({{ $reclamation->categorieLibelle() }}, {{ $reclamation->created_at?->format('d/m') }})</span>
                                </li>
                            @endforeach
                            @foreach ($alerte->avisNegatifs() as $avis)
                                <li class="py-1 border-bottom"><x-etoiles :note="$avis->note" jeu="bx" /> « {{ \Illuminate\Support\Str::limit($avis->commentaire, 110) }} » <span class="text-muted">— {{ $avis->user?->name }}</span></li>
                            @endforeach
                        </ul>
                    </details>
                </div>

                <div class="col-lg-5">
                    @if ($alerte->statut === 'ouverte')
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <form method="POST" action="{{ route('admin.alertes.analyser', $alerte) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary"><i class="bx bx-bot me-1"></i>{{ $alerte->analyse_ia ? 'Réanalyser' : 'Analyser avec DeepSeek' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.alertes.classer', $alerte) }}" onsubmit="return confirm('Classer cette alerte sans action ?');">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary">Classer</button>
                            </form>
                        </div>

                        @php $lots = $alerte->produit?->lots->where('statut', '!=', 'rappele') ?? collect(); @endphp
                        <form method="POST" action="{{ route('admin.alertes.rappel', $alerte) }}" class="p-3 rounded {{ $alerte->rappel_conseille ? 'bg-label-danger' : 'bg-lighter border' }}"
                              onsubmit="return confirm('Rappeler ce lot ? Les clients concernés seront prévenus.');">
                            @csrf
                            <div class="fw-semibold small mb-2"><i class="bx bx-error me-1"></i>{{ $alerte->rappel_conseille ? 'Rappel conseillé par l\'analyse' : 'Déclencher un rappel' }}</div>
                            @if ($lots->isEmpty())
                                <p class="small mb-0 text-muted">Aucun lot rappelable pour ce produit.</p>
                            @else
                                <select name="lot_id" class="form-select form-select-sm mb-2" required>
                                    @foreach ($lots as $lot)
                                        <option value="{{ $lot->id }}" @selected($lot->id === $alerte->lot_id)>{{ $lot->code }} — produit le {{ $lot->date_production?->format('d/m/Y') }}</option>
                                    @endforeach
                                </select>
                                <textarea name="motif_rappel" rows="2" class="form-control form-control-sm mb-2" minlength="10" maxlength="500" required placeholder="Motif affiché aux consommateurs">{{ old('motif_rappel', $alerte->motif_rappel) }}</textarea>
                                @error('motif_rappel')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                                <button class="btn btn-sm btn-danger w-100">Rappeler le lot et prévenir les clients</button>
                            @endif
                        </form>
                    @else
                        <p class="small text-muted mb-0">
                            {{ $alerte->statutLibelle() }} le {{ $alerte->traitee_at?->format('d/m/Y H:i') }} par {{ $alerte->traiteePar?->name ?? '—' }}.
                            @if ($alerte->statut === 'rappel_declenche' && $alerte->lot)
                                <br>Lot <a href="{{ route('tracabilite.show', $alerte->lot) }}" target="_blank">{{ $alerte->lot->code }}</a> : « {{ $alerte->motif_rappel }} »
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bx bx-check-shield fs-1 text-success d-block mb-2"></i>
        Aucune alerte {{ mb_strtolower(\App\Models\AlerteQualite::STATUTS[$statut]['label']) }}.
    </div></div>
@endforelse
@endsection
