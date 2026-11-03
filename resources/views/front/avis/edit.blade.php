@extends('layouts.front')

@section('title', 'Modifier mon avis')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Modifier mon avis'])

<div class="container py-5" style="max-width: 760px;">
    <a href="{{ route('produit.detail', $avis->produit) }}#avis" class="text-primary"><i class="fas fa-arrow-left me-2"></i>{{ $avis->produit->name }}</a>

    @if ($avis->statut !== 'publie')
        <div class="alert alert-warning mt-3 mb-0">
            Statut : <strong>{{ $avis->statutLibelle() }}</strong>@if ($avis->moderation_raison) — {{ $avis->moderation_raison }}@endif.
            Un avis modifié est relu à nouveau.
        </div>
    @endif

    <form method="POST" action="{{ route('avis.update', $avis) }}" class="ra-carte p-4 mt-3">
        @csrf
        @method('PUT')
        @include('front.avis._champs', ['avisCourant' => $avis])
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary text-white rounded-pill px-4">Enregistrer</button>
        </div>
    </form>

    <form method="POST" action="{{ route('avis.destroy', $avis) }}" class="mt-3 text-end" onsubmit="return confirm('Supprimer définitivement votre avis ?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-link text-danger text-decoration-none"><i class="fas fa-trash-alt me-1"></i>Supprimer mon avis</button>
    </form>
</div>
@endsection
