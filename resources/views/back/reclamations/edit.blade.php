@extends('layouts.back')

@section('title', $reclamation->exists ? 'Modifier la réclamation' : 'Nouvelle réclamation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $reclamation->exists ? 'Modifier la réclamation' : 'Nouvelle réclamation' }}</h4>
    <a href="{{ route('admin.reclamations.index') }}" class="btn btn-outline-secondary">Retour</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $reclamation->exists ? route('admin.reclamations.update', $reclamation) : route('admin.reclamations.store') }}">
            @csrf
            @if ($reclamation->exists)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="produit_id">Produit</label>
                    <select class="form-select @error('produit_id') is-invalid @enderror" id="produit_id" name="produit_id" required>
                        <option value="">Sélectionner un produit</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('produit_id', $reclamation->produit_id) === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('produit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="commande_id">Numéro de commande</label>
                    <input class="form-control @error('commande_id') is-invalid @enderror" id="commande_id" name="commande_id" type="number" min="1" value="{{ old('commande_id', $reclamation->commande_id) }}">
                    @error('commande_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="code_lot">Code du lot</label>
                    <input class="form-control @error('code_lot') is-invalid @enderror" id="code_lot" name="code_lot" maxlength="40" value="{{ old('code_lot', $reclamation->lot?->code) }}" placeholder="LOT-2026-0001">
                    @error('code_lot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-8">
                    <label class="form-label" for="sujet">Sujet</label>
                    <input class="form-control @error('sujet') is-invalid @enderror" id="sujet" name="sujet" value="{{ old('sujet', $reclamation->sujet) }}" required>
                    @error('sujet')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="priorite">Priorité</label>
                    <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
                        @foreach (['faible' => 'Faible', 'moyenne' => 'Moyenne', 'haute' => 'Haute', 'urgente' => 'Urgente'] as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(old('priorite', $reclamation->priorite ?: 'moyenne') === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    @error('priorite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if ($reclamation->exists)
                    <div class="col-md-4">
                        <label class="form-label" for="statut">Statut</label>
                        <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                            @foreach (['en_attente' => 'En attente', 'en_cours' => 'En cours', 'resolue' => 'Résolue', 'rejetee' => 'Rejetée'] as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected(old('statut', $reclamation->statut) === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        @error('statut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description', $reclamation->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Enregistrer</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.reclamations.index') }}">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
