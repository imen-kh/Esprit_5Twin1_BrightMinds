@extends('layouts.front')

@section('title', 'Suivi de récolte')

@section('content')
@include('partials.front.page-header', ['titre' => $recolte->nom()])

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <span class="badge {{ $recolte->classeStatut() }}">{{ $recolte->libelleStatut() }}</span>
        <a class="btn btn-outline-primary rounded-pill" href="{{ route('acteur.recoltes.edit', $recolte) }}">Modifier</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="bg-light rounded p-4 h-100">
                <p class="text-muted mb-1">Quantité estimée déclarée</p>
                <h2 class="h3 mb-0">{{ \App\Models\Harvest::formaterKg($recolte->estimated_kg) }} kg</h2>
                <p class="small text-muted mb-0 mt-2">Chiffre saisi par vous, pas un rendement calculé.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-4 h-100">
                <p class="text-muted mb-1">Quantité réellement récoltée</p>
                @if ($recolte->status === \App\Models\Harvest::RECOLTEE)
                    <h2 class="h3 mb-0">{{ \App\Models\Harvest::formaterKg($recolte->harvested_kg) }} kg</h2>
                @else
                    <h2 class="h3 mb-0">Pas encore récoltée</h2>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-4 h-100">
                <p class="text-muted mb-1">Vos dates</p>
                <p class="mb-1">Plantation : {{ $recolte->planted_at->format('d/m/Y') }}</p>
                <p class="mb-1">Récolte prévue : {{ $recolte->planned_harvest_at->format('d/m/Y') }}</p>
                <p class="mb-0">Superficie : {{ $recolte->area_hectares }} ha</p>
            </div>
        </div>
    </div>

    <div class="border rounded p-4 mb-4">
        <h2 class="h5">Fenêtre indicative</h2>
        <p class="mb-1">Du {{ $fiche['fenetre']['debut']->format('d/m/Y') }} au {{ $fiche['fenetre']['fin']->format('d/m/Y') }}.</p>
        <p class="text-muted mb-0">Cycle configuré de {{ $fiche['fenetre']['min_jours'] }} à {{ $fiche['fenetre']['max_jours'] }} jours après la plantation. Ce n’est pas une date garantie.</p>
    </div>

    <div class="mb-4">
        <h2 class="h5">Risques météo sur la date prévue</h2>
        @if ($fiche['risques']['message'])
            <div class="alert alert-warning">{{ $fiche['risques']['message'] }}</div>
        @endif
        @forelse ($fiche['risques']['alertes'] as $alerte)
            <div class="alert alert-{{ $alerte['classe'] }}">
                <strong>{{ $alerte['titre'] }}</strong>
                <p class="mb-1">{{ $alerte['detail'] }}</p>
                <p class="mb-0">{{ $alerte['recommandation'] }}</p>
            </div>
        @empty
            @if (! $fiche['risques']['message'])
                <p class="text-muted mb-0">Aucune alerte sur la date prévue pour les seuils de cette culture.</p>
            @endif
        @endforelse
    </div>

    <div class="bg-light rounded p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Recommandation</h2>
            <span class="badge {{ $fiche['explication_source'] === 'ia' ? 'bg-primary' : 'bg-secondary' }}">
                {{ $fiche['explication_source'] === 'ia' ? 'Générée par IA' : 'Selon les règles' }}
            </span>
        </div>
        <p class="mb-0">{{ $fiche['explication'] }}</p>
    </div>
</div>
@endsection
