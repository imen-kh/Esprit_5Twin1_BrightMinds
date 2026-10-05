@extends('layouts.back')

@section('title', $reclamation->sujet)

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ $reclamation->sujet }}</h4>
        <p class="text-muted mb-0">{{ $reclamation->created_at?->format('d/m/Y H:i') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reclamations.index') }}" class="btn btn-outline-secondary">Retour</a>
        <a href="{{ route('admin.reclamations.edit', $reclamation) }}" class="btn btn-primary">Modifier</a>
        <form action="{{ route('admin.reclamations.destroy', $reclamation) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Supprimer</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span>
                    <span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span>
                </div>
                <p><strong>Consommateur :</strong> {{ $reclamation->user?->name ?? '—' }}</p>
                <p><strong>Produit :</strong> {{ $reclamation->produit?->name ?? '—' }}</p>
                <p><strong>Commande :</strong> {{ $reclamation->commande_id ?: 'Non renseignée' }}</p>
                <p class="mb-0">{{ $reclamation->description }}</p>
            </div>
        </div>

        @if ($reclamation->sentiment)
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Analyse préparée</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Sentiment :</strong> {{ ucfirst($reclamation->sentiment) }}</p>
                    <p class="mb-1"><strong>Priorité suggérée :</strong> {{ $reclamation->priorite_suggeree }}</p>
                    @if ($reclamation->suggestion_reponse)
                        <p class="mb-0"><strong>Suggestion de réponse :</strong> {{ $reclamation->suggestion_reponse }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Réponses</h5></div>
            <div class="card-body">
                @forelse ($reclamation->reponses as $reponse)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>{{ $reponse->user?->name ?? 'Utilisateur' }}</strong>
                            <span class="text-muted small">{{ $reponse->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <form class="mt-2" method="POST" action="{{ route('admin.reponses-reclamations.update', $reponse) }}">
                            @csrf
                            @method('PUT')
                            <textarea class="form-control mb-2" name="message" rows="3" required>{{ $reponse->message }}</textarea>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Modifier</button>
                        </form>
                        <form class="mt-2" method="POST" action="{{ route('admin.reponses-reclamations.destroy', $reponse) }}" onsubmit="return confirm('Supprimer cette réponse ?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune réponse.</p>
                @endforelse

                <form class="mt-4" method="POST" action="{{ route('admin.reclamations.reponses.store', $reclamation) }}">
                    @csrf
                    <label class="form-label" for="message">Répondre à cette réclamation</label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button class="btn btn-primary mt-3" type="submit">Envoyer la réponse</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Statut</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.reclamations.statut', $reclamation) }}">
                    @csrf
                    @method('PATCH')
                    <select class="form-select mb-3" name="statut">
                        @foreach (['en_attente' => 'En attente', 'en_cours' => 'En cours', 'resolue' => 'Résolue', 'rejetee' => 'Rejetée'] as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected($reclamation->statut === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary w-100" type="submit">Mettre à jour le statut</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
