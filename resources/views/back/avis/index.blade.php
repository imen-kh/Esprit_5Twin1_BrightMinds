@extends('layouts.back')

@section('title', 'Avis clients')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">Avis clients</h4>
        <p class="text-muted mb-0">Modération assistée par DeepSeek : insultes, spam et données personnelles sont retenus avant publication.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <small class="text-muted">Note moyenne</small>
            <h3 class="mb-1">{{ $stats['moyenne'] !== null ? str_replace('.', ',', (string) $stats['moyenne']) : '—' }}<small class="text-muted fs-6">/5</small></h3>
            <x-etoiles :note="$stats['moyenne'] ?? 0" jeu="bx" />
        </div></div>
    </div>
    <div class="col-md-3">
        <a href="{{ route('admin.avis.index', ['statut' => 'en_moderation']) }}" class="card h-100 text-decoration-none"><div class="card-body">
            <small class="text-muted">À modérer</small>
            <h3 class="mb-0 text-warning">{{ $stats['moderation'] }}</h3>
            <small class="text-muted">retenus par l'IA ou signalés</small>
        </div></a>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <small class="text-muted">Avis / achats vérifiés</small>
            <h3 class="mb-0">{{ $stats['total'] }} <small class="text-muted fs-6">/ {{ $stats['verifies'] }} vérifiés</small></h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <small class="text-muted d-block mb-2">Répartition des notes</small>
            @php $max = max(1, $stats['repartition']->max() ?? 1); @endphp
            @for ($n = 5; $n >= 1; $n--)
                <div class="d-flex align-items-center gap-2 small">
                    <span style="width: 1.5rem;">{{ $n }}★</span>
                    <div class="ra-barre flex-grow-1"><span style="width: {{ round(100 * ($stats['repartition'][$n] ?? 0) / $max) }}%"></span></div>
                    <span style="width: 1.5rem;" class="text-end">{{ $stats['repartition'][$n] ?? 0 }}</span>
                </div>
            @endfor
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.avis.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Texte, produit ou client"></div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach (\App\Models\Avis::STATUTS as $valeur => $statut)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="note" class="form-select">
                    <option value="">Toutes les notes</option>
                    @for ($n = 5; $n >= 1; $n--)
                        <option value="{{ $n }}" @selected((string) request('note') === (string) $n)>{{ $n }} étoile{{ $n > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-1">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="signales" value="1" id="signales" @checked(request()->boolean('signales'))><label class="form-check-label" for="signales">Signalés</label></div>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Avis</th>
                    <th>Produit</th>
                    <th>Note</th>
                    <th>Analyse IA</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($avis as $unAvis)
                    <tr>
                        <td style="max-width: 360px;">
                            <strong>{{ $unAvis->user?->name }}</strong>
                            @if ($unAvis->achat_verifie)<span class="badge bg-label-success ms-1">Achat vérifié</span>@endif
                            <div class="small text-muted">{{ $unAvis->created_at?->format('d/m/Y H:i') }}</div>
                            @if ($unAvis->titre)<div class="fw-semibold small mt-1">{{ $unAvis->titre }}</div>@endif
                            <div class="small text-wrap">{{ \Illuminate\Support\Str::limit($unAvis->commentaire, 180) }}</div>
                        </td>
                        <td>{{ $unAvis->produit?->name }}</td>
                        <td><x-etoiles :note="$unAvis->note" jeu="bx" /></td>
                        <td class="small">
                            @if ($unAvis->sentiment)<div>Sentiment : <strong>{{ $unAvis->sentiment }}</strong></div>@endif
                            @if ($unAvis->moderation_raison)<div class="text-warning"><i class="bx bx-error-circle"></i> {{ $unAvis->moderation_raison }}</div>@endif
                            @if ($unAvis->signalements_count)<div class="text-danger"><i class="bx bx-flag"></i> {{ $unAvis->signalements_count }} signalement(s)</div>@endif
                            @if ($unAvis->probleme_detecte)<div class="text-info"><i class="bx bx-wrench"></i> Problème à traiter</div>@endif
                        </td>
                        <td><span class="badge {{ $unAvis->statutBadge() }}">{{ $unAvis->statutLibelle() }}</span></td>
                        <td class="text-end text-nowrap">
                            @if ($unAvis->statut !== 'publie')
                                <form method="POST" action="{{ route('admin.avis.publier', $unAvis) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Publier</button></form>
                            @endif
                            @if ($unAvis->statut !== 'masque')
                                <form method="POST" action="{{ route('admin.avis.masquer', $unAvis) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">Masquer</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.avis.destroy', $unAvis) }}" class="d-inline" onsubmit="return confirm('Supprimer définitivement cet avis ?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun avis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($avis->hasPages())
        <div class="card-footer">{{ $avis->links() }}</div>
    @endif
</div>
@endsection
