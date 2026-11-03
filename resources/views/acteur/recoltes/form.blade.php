@extends('layouts.front')

@section('title', $recolte->exists ? 'Modifier la récolte' : 'Planifier une récolte')

@section('content')
@include('partials.front.page-header', ['titre' => $recolte->exists ? 'Modifier la récolte' : 'Planifier une récolte'])

<div class="container py-5">
    <form method="POST" action="{{ $recolte->exists ? route('acteur.recoltes.update', $recolte) : route('acteur.recoltes.store') }}" class="bg-light rounded p-4">
        @csrf
        @if ($recolte->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="culture">Produit cultivé</label>
                <input class="form-control @error('culture') is-invalid @enderror" list="produits-cultives" id="culture" name="culture" value="{{ old('culture', $recolte->nom()) }}" placeholder="Choisir ou écrire, par exemple Pastèque" required maxlength="100" autocomplete="off">
                <datalist id="produits-cultives">
                    @foreach ($produits as $produit)
                        <option value="{{ $produit->name }}"></option>
                    @endforeach
                </datalist>
                <div class="form-text">Choisissez un de vos produits ou saisissez une autre culture. Elle n’est pas ajoutée au catalogue.</div>
                @error('culture') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Statut</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'recoltee' => 'Récoltée'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('status', $recolte->status) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="planted_at">Date de plantation</label>
                <input class="form-control @error('planted_at') is-invalid @enderror" type="date" id="planted_at" name="planted_at" value="{{ old('planted_at', optional($recolte->planted_at)->toDateString()) }}" required>
                @error('planted_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="planned_harvest_at">Date de récolte prévue</label>
                <input class="form-control @error('planned_harvest_at') is-invalid @enderror" type="date" id="planned_harvest_at" name="planned_harvest_at" value="{{ old('planned_harvest_at', optional($recolte->planned_harvest_at)->toDateString()) }}" required>
                @error('planned_harvest_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="area_hectares">Superficie (ha)</label>
                <input class="form-control @error('area_hectares') is-invalid @enderror" type="number" step="0.01" min="0.01" id="area_hectares" name="area_hectares" value="{{ old('area_hectares', $recolte->area_hectares) }}" required>
                @error('area_hectares') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="estimated_kg">Quantité estimée (kg)</label>
                <input class="form-control @error('estimated_kg') is-invalid @enderror" type="number" step="0.1" min="0.1" id="estimated_kg" name="estimated_kg" value="{{ old('estimated_kg', $recolte->estimated_kg) }}" required>
                <div class="form-text">Déclarée par vous. Ce n’est pas un rendement calculé.</div>
                @error('estimated_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="harvested_kg">Quantité réellement récoltée (kg)</label>
                <input class="form-control @error('harvested_kg') is-invalid @enderror" type="number" step="0.1" min="0" id="harvested_kg" name="harvested_kg" value="{{ old('harvested_kg', $recolte->harvested_kg) }}">
                <div class="form-text">Obligatoire seulement quand le statut est Récoltée.</div>
                @error('harvested_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary rounded-pill px-4" type="submit">Enregistrer</button>
            <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ route('acteur.recoltes.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
