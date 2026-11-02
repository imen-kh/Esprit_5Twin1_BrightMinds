@extends('layouts.back')

@section('title', 'Examiner '.$product->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ $product->name }}</h4>
        <p class="mb-0 text-body-secondary">Décision manuelle. L’IA ne peut pas approuver ce produit.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.produits.validation') }}">Retour à la file</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="rounded" style="width: 120px; height: 120px; object-fit: cover;">
                    <div>
                        <span class="badge {{ $product->classeStatut() }}">{{ $product->libelleStatut() }}</span>
                        <p class="mb-1 mt-2"><strong>Catégorie :</strong> {{ $product->category?->name ?: '—' }}</p>
                        <p class="mb-1"><strong>Prix :</strong> {{ $product->formattedPrice() }} DT</p>
                        <p class="mb-1"><strong>Origine :</strong> {{ $product->origin ?: '—' }}</p>
                        <p class="mb-0"><strong>Acteur :</strong> {{ $product->user?->name ?: '—' }}</p>
                    </div>
                </div>
                <p class="mb-2">{{ $product->description }}</p>
                @if ($product->ingredients)
                    <p class="mb-0 text-body-secondary"><strong>Ingrédients :</strong> {{ $product->ingredients }}</p>
                @endif
                @if ($product->review_note)
                    <div class="alert alert-warning mt-3 mb-0">
                        Dernier motif @if ($product->reviewer) ({{ $product->reviewer->name }}) @endif : {{ $product->review_note }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Produits proches</h5>
                <p class="text-body-secondary">Détection locale par nom, y compris si l’IA est indisponible.</p>
                @forelse ($similaires as $autre)
                    <div class="d-flex justify-content-between align-items-center border-top py-2">
                        <div>
                            <div class="fw-medium">{{ $autre->name }}</div>
                            <small class="text-body-secondary">{{ $autre->user?->name ?: '—' }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $autre->classeStatut() }}">{{ $autre->libelleStatut() }}</span>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.produits.revue', $autre) }}">Voir</a>
                        </div>
                    </div>
                @empty
                    <p class="mb-0">Aucun produit similaire détecté.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="mb-4">
            @if ($product->quality_analysis)
                @include('partials.qualite-produit', ['analyse' => $product->quality_analysis])
            @else
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Qualité visuelle</h5>
                        <p class="text-body-secondary mb-0">Aucune analyse de qualité. Le producteur peut la lancer depuis la photo. La validation reste possible sans elle.</p>
                    </div>
                </div>
            @endif
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Analyse IA</h5>
                    <form method="POST" action="{{ route('admin.produits.analyser', $product) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary" type="submit">Analyser avec Gemini</button>
                    </form>
                </div>
                @if ($product->ai_analysis)
                    <p>{{ $product->ai_analysis['resume'] ?? '' }}</p>
                    <p class="mb-2">
                        <span class="badge {{ ($product->ai_analysis['coherent'] ?? false) ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ ($product->ai_analysis['coherent'] ?? false) ? 'Fiche cohérente' : 'Points à vérifier' }}
                        </span>
                    </p>
                    @if (! empty($product->ai_analysis['anomalies']))
                        <p class="fw-medium mb-1">Anomalies</p>
                        <ul>
                            @foreach ($product->ai_analysis['anomalies'] as $anomalie)
                                <li>{{ $anomalie }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($product->ai_analysis['corrections']))
                        <p class="fw-medium mb-1">Corrections suggérées</p>
                        <ul class="mb-0">
                            @foreach ($product->ai_analysis['corrections'] as $correction)
                                <li>{{ $correction }}</li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <p class="text-body-secondary mb-0">Aucune analyse enregistrée. Sans clé Gemini, cette zone reste vide et la validation manuelle continue de fonctionner.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body d-grid gap-3">
                <h5 class="card-title mb-0">Décision</h5>
                @if ($product->status !== \App\Models\Product::PUBLISHED)
                    <form method="POST" action="{{ route('admin.produits.approuver', $product) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-success w-100" type="submit">Approuver et publier</button>
                    </form>
                @else
                    <p class="text-body-secondary mb-0">Ce produit est déjà publié.</p>
                @endif
                <form method="POST" action="{{ route('admin.produits.modification', $product) }}">
                    @csrf @method('PATCH')
                    <label class="form-label" for="motif-modification">Demander une modification</label>
                    <textarea class="form-control mb-2 @error('motif') is-invalid @enderror" id="motif-modification" name="motif" rows="3" placeholder="Expliquez ce qu’il faut corriger (10 caractères minimum)">{{ old('motif') }}</textarea>
                    <button class="btn btn-warning" type="submit">Envoyer la demande</button>
                </form>
                <form method="POST" action="{{ route('admin.produits.refuser', $product) }}">
                    @csrf @method('PATCH')
                    <label class="form-label" for="motif-refus">Refuser</label>
                    <textarea class="form-control mb-2" id="motif-refus" name="motif" rows="3" placeholder="Motif du refus, visible par le producteur"></textarea>
                    <button class="btn btn-outline-danger" type="submit">Refuser le produit</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
