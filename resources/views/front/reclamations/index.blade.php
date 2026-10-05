@extends('layouts.front')

@section('title', 'Mes réclamations')

@section('content')
@include('partials.front.page-header', ['titre' => 'Mes réclamations'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <p class="text-muted mb-0">Déposez un avis ou une réclamation sur un produit.</p>
        <a href="{{ route('reclamations.create') }}" class="btn btn-primary">Nouvelle réclamation</a>
    </div>

    <div class="table-responsive border rounded">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Sujet</th>
                    <th>Produit</th>
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
                        <td>{{ $reclamation->produit?->name ?? '—' }}</td>
                        <td><span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span></td>
                        <td><span class="badge {{ $reclamation->prioriteBadge() }}">{{ $reclamation->prioriteLibelle() }}</span></td>
                        <td>{{ $reclamation->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('reclamations.show', $reclamation) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                            @if ($reclamation->statut === 'en_attente')
                                <a href="{{ route('reclamations.edit', $reclamation) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                <form action="{{ route('reclamations.destroy', $reclamation) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aucune réclamation pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reclamations->hasPages())
        <div class="mt-4">{{ $reclamations->links() }}</div>
    @endif
</div>
@endsection
