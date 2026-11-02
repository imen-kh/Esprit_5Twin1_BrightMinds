@extends('layouts.front')

@section('title', 'Suivre une culture')

@section('content')
@include('partials.front.page-header', ['titre' => 'Suivre une culture'])

<div class="container py-5">
    <form method="POST" action="{{ route('acteur.stocks.store') }}" class="bg-light rounded p-4">
        @csrf
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="culture">Produit ou culture</label>
                <input class="form-control @error('culture') is-invalid @enderror" list="produits-cultives" id="culture" name="culture" value="{{ old('culture') }}" placeholder="Choisir ou écrire, par exemple Pastèque" required maxlength="100" autocomplete="off">
                <datalist id="produits-cultives">
                    @foreach ($produits as $produit)
                        <option value="{{ $produit->name }}"></option>
                    @endforeach
                </datalist>
                <div class="form-text">Un nom identique à l’un de vos produits relie le stock aux commandes de ce produit. Une autre culture n’est pas ajoutée au catalogue.</div>
                @error('culture') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="seuil_kg">Seuil d’alerte (kg)</label>
                <input class="form-control @error('seuil_kg') is-invalid @enderror" type="number" step="0.1" min="0" id="seuil_kg" name="seuil_kg" value="{{ old('seuil_kg', 20) }}" required>
                @error('seuil_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="kg_par_unite">Poids d’une unité vendue (kg)</label>
                <input class="form-control @error('kg_par_unite') is-invalid @enderror" type="number" step="0.001" min="0.001" id="kg_par_unite" name="kg_par_unite" value="{{ old('kg_par_unite') }}">
                <div class="form-text">Les commandes comptent des pièces. Sans ce poids, elles ne sont pas déduites.</div>
                @error('kg_par_unite') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="quantite_initiale">Stock déjà en main (kg)</label>
                <input class="form-control @error('quantite_initiale') is-invalid @enderror" type="number" step="0.1" min="0.1" id="quantite_initiale" name="quantite_initiale" value="{{ old('quantite_initiale') }}">
                <div class="form-text">Laissez vide si la quantité vient d’une récolte déjà marquée Récoltée : elle sera ajoutée une seule fois.</div>
                @error('quantite_initiale') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary rounded-pill px-4" type="submit">Ouvrir le stock</button>
            <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ route('acteur.stocks.index') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
