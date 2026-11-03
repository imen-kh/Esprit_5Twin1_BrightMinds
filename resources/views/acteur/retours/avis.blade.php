@extends('layouts.front')

@section('title', 'Avis sur mes produits')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Retours clients'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Avis sur mes produits</h2>
            <p class="text-muted mb-0">Ce que les clients aiment, ce qu'ils voudraient voir amélioré, et votre droit de réponse.</p>
        </div>
        <a href="{{ route('acteur.reclamations.index') }}" class="btn btn-outline-primary rounded-pill"><i class="fas fa-exclamation-circle me-2"></i>Réclamations</a>
    </div>

    @if ($produits->isNotEmpty())
        <div class="row g-3 mb-4">
            @foreach ($produits as $produit)
                <div class="col-md-6 col-xl-4">
                    <div class="ra-carte p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">{{ $produit->name }}</h6>
                            <span class="text-nowrap"><x-etoiles :note="$produit->note_moyenne" /> <small class="text-muted">({{ $produit->nb_avis }})</small></span>
                        </div>
                        @forelse ($produit->aspects_moyens as $aspect => $valeur)
                            <div class="d-flex align-items-center gap-2 small mb-1">
                                <span style="width: 9rem;">{{ \App\Models\Avis::ASPECTS[$aspect]['label'] }}</span>
                                <div class="ra-barre flex-grow-1 {{ $valeur >= 4 ? 'verte' : ($valeur < 3 ? 'rouge' : '') }}"><span style="width: {{ $valeur * 20 }}%"></span></div>
                                <strong>{{ str_replace('.', ',', (string) $valeur) }}</strong>
                            </div>
                        @empty
                            <p class="small text-muted mb-0">Pas encore assez de détails dans les avis.</p>
                        @endforelse
                        @php $faible = collect($produit->aspects_moyens)->filter(fn ($v) => $v < 3)->keys()->first(); @endphp
                        @if ($faible)
                            <p class="small mt-2 mb-0 text-danger"><i class="fas fa-lightbulb me-1"></i>Piste d'amélioration : {{ mb_strtolower(\App\Models\Avis::ASPECTS[$faible]['label']) }}.</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('acteur.avis.index') }}" class="btn btn-sm rounded-pill {{ request()->boolean('sans_reponse') ? 'btn-outline-secondary' : 'btn-secondary text-white' }}">Tous</a>
        <a href="{{ route('acteur.avis.index', ['sans_reponse' => 1]) }}" class="btn btn-sm rounded-pill {{ request()->boolean('sans_reponse') ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">Sans réponse</a>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse ($avis as $unAvis)
            <article class="ra-avis">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <strong>{{ $unAvis->user?->name }}</strong>
                        @if ($unAvis->achat_verifie)<span class="ra-verifie ms-1">Achat vérifié</span>@endif
                        <div class="small text-muted">{{ $unAvis->produit?->name }} · {{ $unAvis->created_at?->format('d/m/Y') }}</div>
                    </div>
                    <x-etoiles :note="$unAvis->note" />
                </div>
                @if ($unAvis->titre)<h6 class="mt-2 mb-1">{{ $unAvis->titre }}</h6>@endif
                <p class="mb-2">{{ $unAvis->commentaire }}</p>

                @can('repondre', $unAvis)
                    <form method="POST" action="{{ route('acteur.avis.repondre', $unAvis) }}" class="mt-2">
                        @csrf
                        <label class="form-label small fw-bold" for="reponse-{{ $unAvis->id }}">{{ $unAvis->reponse_producteur ? 'Modifier ma réponse publique' : 'Répondre publiquement' }}</label>
                        <textarea id="reponse-{{ $unAvis->id }}" name="reponse_producteur" rows="2" maxlength="1000" class="form-control mb-2" required>{{ $unAvis->reponse_producteur }}</textarea>
                        <button class="btn btn-sm btn-primary text-white rounded-pill">Publier la réponse</button>
                    </form>
                @elseif ($unAvis->reponse_producteur)
                    <div class="ra-reponse-producteur small">{{ $unAvis->reponse_producteur }}</div>
                @endcan
            </article>
        @empty
            <div class="text-center text-muted py-5 ra-carte">Aucun avis publié sur vos produits pour le moment.</div>
        @endforelse
    </div>

    @if ($avis->hasPages())
        <div class="mt-4">{{ $avis->links() }}</div>
    @endif
</div>
@endsection
