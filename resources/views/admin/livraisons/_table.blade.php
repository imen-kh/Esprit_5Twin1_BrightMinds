<div class="card mb-5">
    <div class="card-header"><h5 class="mb-0">{{ $titre }}</h5></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>ID</th><th>Commande</th><th>Utilisateur</th><th>Distributeur</th><th>Statut</th><th>N° suivi</th><th>Date prévue</th><th>Action</th></tr></thead><tbody>
    @forelse($livraisons as $livraison)
        <tr><td>#{{ $livraison->id }}</td><td>#{{ $livraison->commande_id }}</td><td>{{ $livraison->commande->user?->name ?: $livraison->commande->email }}</td><td>{{ $livraison->distributeur?->name ?: 'Non affecté' }}</td><td>{{ ucfirst(str_replace('_', ' ', $livraison->statut)) }}</td><td>{{ $livraison->numero_suivi ?: '—' }}</td><td>{{ $livraison->date_livraison_prevue?->format('d/m/Y') ?: '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.livraisons.show', $livraison) }}">Voir</a></td></tr>
    @empty
        <tr><td colspan="8" class="text-center">Aucune livraison dans cette catégorie.</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="card-footer">{{ $livraisons->appends(request()->except($pagination))->links() }}</div>
</div>
