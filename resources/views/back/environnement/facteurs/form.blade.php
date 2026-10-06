@extends('layouts.back')

@section('title', $facteur->exists ? 'Modifier le facteur d\'émission' : 'Nouveau facteur d\'émission')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">{{ $facteur->exists ? 'Modifier le facteur' : 'Nouveau facteur d\'émission' }}</h4>
    <p class="text-muted mb-0">Paramétrez le coefficient d'émission carbone utilisé dans les bilans environnementaux.</p>
  </div>
  <a href="{{ route('admin.facteurs-emission.index') }}" class="btn btn-outline-secondary">
    <i class="bx bx-arrow-back me-1"></i> Retour à la liste
  </a>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ $facteur->exists ? route('admin.facteurs-emission.update', $facteur) : route('admin.facteurs-emission.store') }}">
      @csrf
      @if ($facteur->exists)
        @method('PUT')
      @endif

      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="form-label" for="nom">Nom du facteur <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom', $facteur->nom) }}" required placeholder="Ex: Transport routier — Camion diesel 16-32t">
          @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label" for="code">Code unique <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $facteur->code) }}" required placeholder="Ex: TRSP_CAMION_DIESEL">
          @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label" for="categorie">Catégorie <span class="text-danger">*</span></label>
          <select class="form-select @error('categorie') is-invalid @enderror" id="categorie" name="categorie" required>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}" @selected(old('categorie', $facteur->categorie) === $cat)>{{ ucfirst($cat) }}</option>
            @endforeach
          </select>
          @error('categorie')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label" for="valeur">Valeur numérique <span class="text-danger">*</span></label>
          <input type="number" step="0.0001" min="0" class="form-control @error('valeur') is-invalid @enderror" id="valeur" name="valeur" value="{{ old('valeur', $facteur->valeur) }}" required placeholder="Ex: 0.1600">
          @error('valeur')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label" for="unite">Unité de mesure <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('unite') is-invalid @enderror" id="unite" name="unite" value="{{ old('unite', $facteur->unite) }}" required placeholder="Ex: kg CO2e / t.km ou kg CO2e / kg">
          @error('unite')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label" for="source">Source documentaire / Organisme</label>
          <input type="text" class="form-control @error('source') is-invalid @enderror" id="source" name="source" value="{{ old('source', $facteur->source) }}" placeholder="Ex: Base Empreinte ADEME, ANME Tunisie, GIEC">
          @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 mb-3 d-flex align-items-center pt-3">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" @checked(old('actif', $facteur->exists ? $facteur->actif : true))>
            <label class="form-check-label" for="actif">Facteur actif et disponible pour les calculs</label>
          </div>
        </div>

        <div class="col-12 mb-4">
          <label class="form-label" for="description">Description & Hypothèses de calcul</label>
          <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Précisez le périmètre de calcul, les hypothèses de mix énergétique ou les normes prises en compte...">{{ old('description', $facteur->description) }}</textarea>
          @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-save me-1"></i> {{ $facteur->exists ? 'Mettre à jour' : 'Enregistrer' }}
        </button>
        <a href="{{ route('admin.facteurs-emission.index') }}" class="btn btn-label-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>
@endsection
