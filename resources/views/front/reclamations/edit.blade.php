@extends('layouts.front')

@section('title', 'Modifier la réclamation')

@section('content')
@include('partials.front.page-header', ['titre' => 'Modifier la réclamation'])

<div class="container py-5">
    <form class="border rounded p-4" method="POST" action="{{ route('reclamations.update', $reclamation) }}">
        @include('front.reclamations._form')
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-secondary" href="{{ route('reclamations.show', $reclamation) }}">Annuler</a>
    </form>
</div>
@endsection
