@extends('layouts.back')

@section('title', 'Réclamations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">Réclamations</h4>
        <p class="text-muted mb-0">Tri automatique : dossiers en retard, puis urgents. Analyse et brouillons de réponse assistés par IA.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.alertes.index') }}" class="btn btn-outline-danger"><i class="bx bx-error me-1"></i>Alertes qualité</a>
        <a href="{{ route('admin.reclamations.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Nouvelle réclamation</a>
    </div>
</div>

<div class="row g-4 mb-4">
    @foreach ([
        ['Ouvertes', $kpis['ouvertes'], 'bx-folder-open', 'primary', route('admin.reclamations.index', ['statut' => 'en_attente'])],
        ['En retard (SLA)', $kpis['en_retard'], 'bx-time-five', 'danger', route('admin.reclamations.index', ['retard' => 1])],
        ['Urgentes', $kpis['urgentes'], 'bx-first-aid', 'warning', route('admin.reclamations.index', ['priorite' => 'urgente'])],
        ['Délais respectés', $kpis['respect_sla'] !== null ? $kpis['respect_sla'].' %' : '—', 'bx-check-shield', 'success', null],
        ['Satisfaction', $kpis['satisfaction'] !== null ? str_replace('.', ',', (string) $kpis['satisfaction']).'/5' : '—', 'bx-happy-heart-eyes', 'info', null],
        ['Délai moyen', $kpis['delai_moyen'] !== null ? $kpis['delai_moyen'].' h' : '—', 'bx-timer', 'secondary', null],
    ] as [$libelle, $valeur, $icone, $couleur, $lien])
        <div class="col-6 col-md-4 col-xl-2">
            <a @if ($lien) href="{{ $lien }}" @endif class="card h-100 text-decoration-none">
                <div class="card-body">
                    <span class="avatar-initial rounded bg-label-{{ $couleur }} p-2 d-inline-flex mb-2"><i class="bx {{ $icone }} fs-4"></i></span>
                    <h4 class="mb-0">{{ $valeur }}</h4>
                    <small class="text-muted">{{ $libelle }}</small>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reclamations.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Référence, sujet, client, produit">
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach (\App\Models\Reclamation::statutsLibelles() as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="priorite" class="form-select">
                    <option value="">Toutes les priorités</option>
                    @foreach (\App\Models\Reclamation::prioritesLibelles() as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('priorite') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="categorie" class="form-select">
                    <option value="">Toutes les catégories</option>
                    @foreach (\App\Models\Reclamation::CATEGORIES as $valeur => $categorie)
                        <option value="{{ $valeur }}" @selected(request('categorie') === $valeur)>{{ $categorie['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <div class="form-check text-nowrap">
                    <input class="form-check-input" type="checkbox" name="retard" value="1" id="retard" @checked(request()->boolean('retard'))>
                    <label class="form-check-label" for="retard">Retard</label>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                <a href="{{ route('admin.reclamations.index') }}" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bx bx-reset"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Réclamation</th>
                    <th>Consommateur</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Priorité</th>
                    <th>Délai</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reclamations as $reclamation)
                    <tr class="{{ $reclamation->estEnRetard() ? 'table-danger' : '' }}">
                        <td style="min-width: 260px;">
                            <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="fw-semibold">{{ $reclamation->sujet }}</a>
                            <div class="small text-muted"><span class="ra-reference">{{ $reclamation->reference }}</span> · {{ $reclamation->created_at?->format('d/m/Y') }}@if ($reclamation->source !== 'formulaire') · <i class="bx {{ $reclamation->source === 'assistant' ? 'bx-bot' : 'bx-star' }}"></i> {{ $reclamation->sourceLibelle() }}@endif</div>
                        </td>
                        <td>{{ $reclamation->user?->name ?? '—' }}</td>
                        <td>{{ $reclamation->produit?->name ?? '—' }}</td>
                        <td class="small text-nowrap"><i class="bx {{ $reclamation->categorieIcone() }} me-1"></i>{{ $reclamation->categorieLibelle() }}</td>
                        <td><span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span></td>
                        <td><span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span></td>
                        <td><span class="badge {{ $reclamation->slaBadge() }}">{{ $reclamation->slaLibelle() }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="btn btn-sm btn-icon btn-outline-info" title="Voir" aria-label="Voir"><i class="bx bx-show"></i></a>
                            <a href="{{ route('admin.reclamations.edit', $reclamation) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Modifier" aria-label="Modifier"><i class="bx bx-edit"></i></a>
                            <form action="{{ route('admin.reclamations.destroy', $reclamation) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Supprimer" aria-label="Supprimer"><i class="bx bx-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucune réclamation.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($reclamations->hasPages())
        <div class="card-footer">{{ $reclamations->links() }}</div>
    @endif
</div>
@endsection
