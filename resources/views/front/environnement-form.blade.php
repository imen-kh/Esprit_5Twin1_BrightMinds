@extends('layouts.front')

@section('title', $bilan->exists ? 'Modifier le bilan' : 'Nouveau bilan')

@section('content')
@include('partials.front.page-header', ['titre' => $bilan->exists ? 'Modifier le bilan environnemental' : 'Nouveau bilan environnemental'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form class="border rounded p-4" method="POST" action="{{ $bilan->exists ? route('front.environnement.update', $bilan) : route('front.environnement.store') }}">
        @csrf
        @if ($bilan->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="produit_id">Produit</label>
                <select class="form-select @error('produit_id') is-invalid @enderror" id="produit_id" name="produit_id" required>
                    <option value="">Sélectionner un produit</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-name="{{ $product->name }}" @selected((string) old('produit_id', $bilan->produit_id) === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback">@error('produit_id'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="facteur_emission_id">Facteur d'émission</label>
                <select class="form-select @error('facteur_emission_id') is-invalid @enderror" id="facteur_emission_id" name="facteur_emission_id" required>
                    <option value="">Sélectionner un facteur</option>
                    @foreach ($facteurs as $facteur)
                        <option value="{{ $facteur->id }}" @selected((string) old('facteur_emission_id', $bilan->facteur_emission_id) === (string) $facteur->id)>
                            {{ $facteur->nom }} — {{ $facteur->categorie }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback">@error('facteur_emission_id'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="quantite_produit">Quantité</label>
                <input class="form-control @error('quantite_produit') is-invalid @enderror" id="quantite_produit" name="quantite_produit" type="number" step="0.01" min="0.01" value="{{ old('quantite_produit', $bilan->quantite_produit ?? 1) }}" required>
                <div class="invalid-feedback">@error('quantite_produit'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="unite_mesure">Unité</label>
                <input class="form-control @error('unite_mesure') is-invalid @enderror" id="unite_mesure" name="unite_mesure" value="{{ old('unite_mesure', $bilan->unite_mesure ?? 'kg') }}" required>
                <div class="invalid-feedback">@error('unite_mesure'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="statut">Statut</label>
                <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                    <option value="valide" @selected(old('statut', $bilan->statut) === 'valide')>Validé (visible sur le site)</option>
                    <option value="en_attente" @selected(old('statut', $bilan->statut) === 'en_attente')>En attente</option>
                    <option value="archive" @selected(old('statut', $bilan->statut) === 'archive')>Archivé</option>
                </select>
                <div class="invalid-feedback">@error('statut'){{ $message }}@enderror</div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="origine_geographique">Origine</label>
                <input class="form-control" id="origine_geographique" name="origine_geographique" value="{{ old('origine_geographique', $bilan->origine_geographique) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label" for="distance_km">Distance (km)</label>
                <input class="form-control" id="distance_km" name="distance_km" type="number" min="0" step="1" value="{{ old('distance_km', $bilan->distance_km ?? 0) }}">
            </div>

            <div class="col-md-3">
                <label class="form-label" for="consommation_eau_l">Consommation d'eau (L)</label>
                <input class="form-control" id="consommation_eau_l" name="consommation_eau_l" type="number" min="0" step="1" value="{{ old('consommation_eau_l', $bilan->consommation_eau_l ?? 0) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="mode_culture">Mode de culture</label>
                <select class="form-select" id="mode_culture" name="mode_culture">
                    @foreach (['bio' => 'Agriculture biologique', 'plein_champ' => 'Plein champ', 'hydroponique' => 'Hydroponique', 'serre_chauffee' => 'Serre chauffée'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('mode_culture', $bilan->mode_culture) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="mode_transport">Transport</label>
                <select class="form-select" id="mode_transport" name="mode_transport">
                    @foreach (['camion' => 'Camion', 'train' => 'Train', 'bateau' => 'Bateau', 'avion' => 'Avion'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('mode_transport', $bilan->mode_transport) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="type_emballage">Emballage</label>
                <select class="form-select" id="type_emballage" name="type_emballage">
                    @foreach (['vrac' => 'Vrac', 'carton' => 'Carton', 'verre' => 'Verre', 'plastique' => 'Plastique'] as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('type_emballage', $bilan->type_emballage) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="poids_emballage_g">Poids emballage (g)</label>
                <input class="form-control" id="poids_emballage_g" name="poids_emballage_g" type="number" min="0" step="0.5" value="{{ old('poids_emballage_g', $bilan->poids_emballage_g ?? 0) }}">
            </div>

            <div class="col-12">
                <label class="form-label" for="commentaires">Commentaire</label>
                <textarea class="form-control" id="commentaires" name="commentaires" rows="3">{{ old('commentaires', $bilan->commentaires) }}</textarea>
            </div>

            @if ($bilan->exists)
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="recalculer_ia" name="recalculer_ia" value="1">
                        <label class="form-check-label" for="recalculer_ia">Recalculer le score environnemental</label>
                    </div>
                </div>
            @endif
        </div>

        <p class="text-muted small mt-3 mb-4">Le score et les émissions CO2 sont calculés automatiquement à l'enregistrement.</p>

        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-secondary" href="{{ route('front.environnement.index') }}">Annuler</a>
    </form>
</div>
@endsection
