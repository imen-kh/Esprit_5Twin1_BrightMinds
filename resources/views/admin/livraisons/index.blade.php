@extends('layouts.back')
@section('title', 'Livraisons')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3">Livraisons</h4>
    <form class="row g-2 mb-4">
        <div class="col-md-3"><input name="q" class="form-control" placeholder="N° de suivi" value="{{ request('q') }}"></div>
        <div class="col-md-3"><select name="statut" class="form-select"><option value="">Tous les statuts</option>@foreach(\App\Models\Livraison::STATUTS as $statut)<option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="distributeur_id" class="form-select"><option value="">Tous les distributeurs</option>@foreach($distributeurs as $distributeur)<option value="{{ $distributeur->id }}" @selected((string) request('distributeur_id') === (string) $distributeur->id)>{{ $distributeur->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary">Filtrer</button></div>
    </form>

    @if($commandesSansLivraison->isNotEmpty())
        <div class="card mb-5"><div class="card-header"><h5 class="mb-0">Commandes confirmées sans livraison</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Commande</th><th>Utilisateur</th><th>Total</th><th>Statut</th><th>Action</th></tr></thead><tbody>@foreach($commandesSansLivraison as $commande)<tr><td>#{{ $commande->id }}</td><td>{{ $commande->user?->name ?: $commande->email }}</td><td>{{ number_format($commande->total, 3, ',', ' ') }} DT</td><td>Confirmée</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.commandes.show', $commande) }}">Voir la commande</a></td></tr>@endforeach</tbody></table></div></div>
    @endif

    @include('admin.livraisons._table', ['titre' => 'Livraisons des transformateurs', 'livraisons' => $livraisonsTransformateurs, 'pagination' => 'transformateur_page'])
    @include('admin.livraisons._table', ['titre' => 'Livraisons des consommateurs', 'livraisons' => $livraisonsConsommateurs, 'pagination' => 'consommateur_page'])
</div>
@endsection
