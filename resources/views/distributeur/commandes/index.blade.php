@extends('layouts.front')
@section('title', 'Commandes à traiter')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Commandes à traiter</h1></div>
<div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="table-responsive"><table class="table"><thead><tr><th>Commande</th><th>Transformateur</th><th>Date</th><th>Total</th><th>Statut</th><th>Action</th></tr></thead><tbody>
    @forelse($commandes as $commande)<tr><td>#{{ $commande->id }}</td><td>{{ $commande->user->name }}</td><td>{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y') }}</td><td>{{ number_format($commande->total, 3, ',', ' ') }} DT</td><td>{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('distributeur.commandes.show', $commande) }}">Voir</a></td></tr>@empty
    <tr><td colspan="6" class="text-center">Aucune commande à traiter.</td></tr>@endforelse
    </tbody></table></div>{{ $commandes->links() }}
</div>
@endsection
