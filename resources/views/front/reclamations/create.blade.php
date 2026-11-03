@extends('layouts.front')

@section('title', 'Nouvelle réclamation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Nouvelle réclamation'])

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <form class="ra-carte p-4" method="POST" action="{{ route('reclamations.store') }}">
                <h2 class="h4 mb-4">Décrivez votre problème</h2>
                @include('front.reclamations._form')
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary text-white rounded-pill px-4" type="submit"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
                    <a class="btn btn-outline-secondary rounded-pill" href="{{ route('reclamations.index') }}">Annuler</a>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="ra-carte-ia p-4 mb-3">
                <span class="ra-badge-ia mb-2"><i class="fas fa-robot"></i>Analysé par l'IA</span>
                <p class="small mb-0 mt-2">À l'envoi, DeepSeek classe votre réclamation (qualité, livraison, emballage…), détecte sa langue et son urgence, et prépare une première réponse pour notre équipe.</p>
            </div>
            <div class="ra-carte p-4">
                <h6><i class="far fa-clock text-primary me-2"></i>Nos délais de traitement</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach (config('reclamations.sla_heures') as $priorite => $heures)
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span>{{ \App\Models\Reclamation::prioritesLibelles()[$priorite] }}</span>
                            <strong>{{ $heures }} h</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
