@extends('layouts.back')
@section('title', 'Commandes')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h4 class="fw-bold py-3 mb-4">Commandes</h4>
@if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif
<form class="row g-2 mb-4"><div class="col-md-4"><input name="q" class="form-control" placeholder="Rechercher une commande..." value="{{ request('q') }}"></div><div class="col-md-3"><select name="statut" class="form-select"><option value="">Tous les statuts</option>@foreach(\App\Models\Commande::STATUTS as $statut)<option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-primary">Filtrer</button></div></form>
@include('admin.commandes._table', ['titre' => 'Toutes les commandes des utilisateurs', 'commandes' => $commandes, 'pagination' => 'commandes_page'])
@include('admin.commandes._table', ['titre' => 'Commandes des transformateurs', 'commandes' => $commandesTransformateurs, 'pagination' => 'transformateur_page'])
@include('admin.commandes._table', ['titre' => 'Commandes des consommateurs', 'commandes' => $commandesConsommateurs, 'pagination' => 'consommateur_page'])
</div>
@endsection
