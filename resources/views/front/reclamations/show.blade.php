@extends('layouts.front')

@section('title', $reclamation->sujet)

@section('content')
@include('partials.front.page-header', ['titre' => 'Réclamation'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('reclamations.index') }}" class="btn btn-outline-secondary">Retour</a>
        @if ($reclamation->user_id === auth()->id() && $reclamation->statut === 'en_attente')
            <a href="{{ route('reclamations.edit', $reclamation) }}" class="btn btn-primary">Modifier</a>
            <form action="{{ route('reclamations.destroy', $reclamation) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Supprimer</button>
            </form>
        @endif
    </div>

    <div class="border rounded p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span>
            <span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span>
        </div>
        <h2 class="h4">{{ $reclamation->sujet }}</h2>
        <p class="text-muted">{{ $reclamation->created_at?->format('d/m/Y H:i') }}</p>
        <p><strong>Produit :</strong> {{ $reclamation->produit?->name ?? '—' }}</p>
        <p><strong>Commande :</strong> {{ $reclamation->commande_id ?: 'Non renseignée' }}</p>
        <p class="mb-0">{{ $reclamation->description }}</p>
    </div>

    <h3 class="h5 mb-3">Réponses</h3>
    @forelse ($reclamation->reponses as $reponse)
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between">
                <strong>{{ $reponse->user?->name ?? 'Utilisateur' }}</strong>
                <span class="text-muted small">{{ $reponse->created_at?->format('d/m/Y H:i') }}</span>
            </div>
            <p class="mb-0 mt-2">{{ $reponse->message }}</p>
        </div>
    @empty
        <p class="text-muted">Aucune réponse pour le moment.</p>
    @endforelse

    <form class="border rounded p-4 mt-4" method="POST" action="{{ route('reclamations.reponses.store', $reclamation) }}">
        @csrf
        <h3 class="h5">Répondre à cette réclamation</h3>
        <div class="mb-3">
            <label class="form-label" for="message">Message</label>
            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
            <div class="invalid-feedback">@error('message'){{ $message }}@enderror</div>
        </div>
        <button class="btn btn-primary" type="submit">Envoyer la réponse</button>
    </form>
</div>
@endsection
