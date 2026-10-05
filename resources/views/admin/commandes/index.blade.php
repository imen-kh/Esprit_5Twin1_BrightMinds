@extends('layouts.back')
@section('title', 'Commandes')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h4 class="fw-bold py-3 mb-4">Commandes</h4>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form class="row g-2 mb-4"><div class="col-md-4"><input name="q" class="form-control" placeholder="Rechercher..." value="{{ request('q') }}"></div><div class="col-md-3"><select name="statut" class="form-select"><option value="">Tous les statuts</option>@foreach(\App\Models\Commande::STATUTS as $statut)<option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-primary">Filtrer</button></div></form>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Transformateur</th><th>Date</th><th>Total</th><th>Statut</th><th>Livraison</th><th>Actions</th></tr></thead><tbody>
@forelse($commandes as $commande)<tr><td>#{{ $commande->id }}</td><td>{{ $commande->user?->name ?: $commande->email }}</td><td>{{ ($commande->date_commande ?? $commande->created_at)->format('d/m/Y') }}</td><td>{{ number_format($commande->total, 3, ',', ' ') }} DT</td><td>{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</td><td>{{ $commande->livraison ? ucfirst(str_replace('_', ' ', $commande->livraison->statut)) : '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.commandes.show', $commande) }}">Voir</a></td></tr>@empty<tr><td colspan="7" class="text-center">Aucune commande.</td></tr>@endforelse
</tbody></table></div></div>{{ $commandes->links() }}</div>
@endsection
