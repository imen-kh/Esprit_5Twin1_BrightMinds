<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Commande #</th><th>Date</th><th>Nb produits</th><th>Total</th><th>Statut</th><th>Livraison</th><th>Action</th></tr></thead><tbody>
@forelse($commandes as $commande)
    @php
        $statuts = ['en_attente' => ['En attente', 'warning'], 'confirmee' => ['Confirmée', 'success'], 'preparee' => ['Préparée', 'info'], 'expediee' => ['Expédiée', 'primary'], 'en_transit' => ['En transit', 'primary'], 'livree' => ['Livrée', 'success'], 'refusee' => ['Refusée', 'danger'], 'annulee' => ['Annulée', 'danger']];
        [$statutLibelle, $statutClasse] = $statuts[$commande->statut] ?? [ucfirst($commande->statut), 'secondary'];
    @endphp
    <tr><td>#{{ str_pad($commande->id, 5, '0', STR_PAD_LEFT) }}</td><td>{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y à H:i') }}</td><td>{{ $commande->products->sum('pivot.quantite') }}</td><td>{{ \App\Models\Product::formatAmount($commande->total) }} DT</td><td class="text-{{ $statutClasse }} fw-bold">{{ $statutLibelle }}</td><td>{{ $commande->livraison ? ucfirst(str_replace('_', ' ', $commande->livraison->statut)) : 'En attente' }}</td><td><a href="{{ route('commandes.show', $commande) }}" class="btn btn-sm btn-outline-primary">Voir</a></td></tr>
@empty
    <tr><td colspan="7" class="text-center py-4">Aucune commande enregistrée.</td></tr>
@endforelse
</tbody></table></div>{{ $commandes->links() }}
