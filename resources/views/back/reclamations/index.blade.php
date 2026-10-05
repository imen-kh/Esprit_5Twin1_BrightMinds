@extends('layouts.back')

@section('title', 'Réclamations')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">Avis et réclamations</h4>
        <p class="text-muted mb-0">Consultez, traitez et répondez aux réclamations des consommateurs.</p>
    </div>
    <a href="{{ route('admin.reclamations.create') }}" class="btn btn-primary">Nouvelle réclamation</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reclamations.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Sujet, consommateur ou produit">
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach (['en_attente' => 'En attente', 'en_cours' => 'En cours', 'resolue' => 'Résolue', 'rejetee' => 'Rejetée'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="priorite" class="form-select">
                    <option value="">Toutes les priorités</option>
                    @foreach (['faible' => 'Faible', 'moyenne' => 'Moyenne', 'haute' => 'Haute', 'urgente' => 'Urgente'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('priorite') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Sujet</th>
                    <th>Consommateur</th>
                    <th>Produit</th>
                    <th>Commande</th>
                    <th>Statut</th>
                    <th>Priorité</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reclamations as $reclamation)
                    <tr>
                        <td>{{ $reclamation->sujet }}</td>
                        <td>{{ $reclamation->user?->name ?? '—' }}</td>
                        <td>{{ $reclamation->produit?->name ?? '—' }}</td>
                        <td>{{ $reclamation->commande_id ?: '—' }}</td>
                        <td><span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span></td>
                        <td><span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span></td>
                        <td>{{ $reclamation->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="btn btn-sm btn-outline-info">Voir</a>
                            <a href="{{ route('admin.reclamations.edit', $reclamation) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                            <form action="{{ route('admin.reclamations.destroy', $reclamation) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
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
