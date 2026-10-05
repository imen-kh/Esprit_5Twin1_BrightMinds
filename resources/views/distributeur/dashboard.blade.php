@extends('layouts.front')
@section('title', 'Dashboard Distributeur')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Dashboard Distributeur</h1></div>
<div class="container py-5"><div class="row g-4">
@foreach([['Commandes confirmées', $stats['commandes'], 'primary'], ['À préparer', $stats['a_preparer'], 'warning'], ['Expédiées', $stats['expediees'], 'info'], ['En transit', $stats['transit'], 'secondary'], ['Livrées', $stats['livrees'], 'success']] as [$label, $value, $color])<div class="col-md"><div class="card h-100"><div class="card-body"><p class="text-muted mb-2">{{ $label }}</p><h2 class="text-{{ $color }}">{{ $value }}</h2></div></div></div>@endforeach
</div><div class="mt-4"><a class="btn btn-primary me-2" href="{{ route('distributeur.commandes.index') }}">Commandes à traiter</a><a class="btn btn-outline-primary" href="{{ route('distributeur.livraisons.index') }}">Mes livraisons</a></div></div>
@endsection
