@extends('layouts.front')
@section('title', 'Mes commandes consommateur')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Mes commandes consommateur</h1></div>
<div class="container py-5"><h2 class="mb-4">Historique de mes achats</h2>
    @include('front.commandes._liste', ['commandes' => $commandes])
</div>
@endsection
