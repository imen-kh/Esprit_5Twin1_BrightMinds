@extends('layouts.front')

@section('title', 'Modifier la réclamation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Modifier la réclamation'])

<div class="container py-5" style="max-width: 900px;">
    <form class="ra-carte p-4" method="POST" action="{{ route('reclamations.update', $reclamation) }}">
        <p class="text-muted"><span class="ra-reference">{{ $reclamation->reference }}</span> — modifiable tant que le dossier n'est pas pris en charge.</p>
        @include('front.reclamations._form')
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary text-white rounded-pill px-4" type="submit">Enregistrer</button>
            <a class="btn btn-outline-secondary rounded-pill" href="{{ route('reclamations.show', $reclamation) }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
