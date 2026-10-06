@extends('layouts.front')

@section('title', 'Nouvelle réclamation')

@section('content')
@include('partials.front.page-header', ['titre' => 'Nouvelle réclamation'])

<div class="container py-5">
    <form class="border rounded p-4" method="POST" action="{{ route('reclamations.store') }}">
        @include('front.reclamations._form')
        <button class="btn btn-primary" type="submit">Envoyer</button>
        <a class="btn btn-secondary" href="{{ route('reclamations.index') }}">Annuler</a>
    </form>
</div>
@endsection
