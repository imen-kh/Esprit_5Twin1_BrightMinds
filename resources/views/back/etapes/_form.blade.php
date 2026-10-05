{{--
  Champs du formulaire d'une étape, partagés par la création et la modification.
  Variables : $etape (nouvelle ou existante), $lots (accessibles), $types (selon le type d'acteur).
--}}
<div class="row g-6">
  <div class="col-md-7">
    <label for="lot_id" class="form-label">Lot</label>
    <select class="form-select @error('lot_id') is-invalid @enderror" id="lot_id" name="lot_id">
      <option value="">Choisissez un lot</option>
      @foreach ($lots as $lot)
        <option value="{{ $lot->id }}" @selected((string) old('lot_id', $etape->lot_id ?? null) === (string) $lot->id)>
          {{ $lot->code }} — {{ $lot->produit->name }}
        </option>
      @endforeach
    </select>
    @error('lot_id')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-5">
    <label for="type_etape" class="form-label">Type d'étape</label>
    <select class="form-select @error('type_etape') is-invalid @enderror" id="type_etape" name="type_etape">
      <option value="">Choisissez un type</option>
      @foreach ($types as $cle => $infos)
        <option value="{{ $cle }}" @selected(old('type_etape', $etape->type_etape ?? null) === $cle)>{{ $infos['label'] }}</option>
      @endforeach
    </select>
    @error('type_etape')
      <div class="invalid-feedback">{{ $message }}</div>
    @else
      @unless (auth()->user()->isAdmin())
        <div class="form-text">Types proposés selon votre activité de {{ auth()->user()->type_acteur }}.</div>
      @endunless
    @enderror
  </div>

  <div class="col-md-7">
    <label for="titre" class="form-label">Titre</label>
    <input
      type="text"
      class="form-control @error('titre') is-invalid @enderror"
      id="titre"
      name="titre"
      value="{{ old('titre', $etape->titre ?? null) }}"
      placeholder="Ex. Récolte manuelle des fraises" />
    @error('titre')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-5">
    <label for="date_etape" class="form-label">Date et heure</label>
    <input
      type="datetime-local"
      class="form-control @error('date_etape') is-invalid @enderror"
      id="date_etape"
      name="date_etape"
      value="{{ old('date_etape', $etape->date_etape?->format('Y-m-d\TH:i')) }}" />
    @error('date_etape')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="lieu" class="form-label">Lieu</label>
    <input
      type="text"
      class="form-control @error('lieu') is-invalid @enderror"
      id="lieu"
      name="lieu"
      value="{{ old('lieu', $etape->lieu ?? null) }}"
      placeholder="Ex. Nabeul" />
    @error('lieu')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-3">
    <label for="latitude" class="form-label">Latitude</label>
    <input
      type="text"
      class="form-control @error('latitude') is-invalid @enderror"
      id="latitude"
      name="latitude"
      value="{{ old('latitude', $etape->latitude ?? null) }}"
      placeholder="36.4561" />
    @error('latitude')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-3">
    <label for="longitude" class="form-label">Longitude</label>
    <input
      type="text"
      class="form-control @error('longitude') is-invalid @enderror"
      id="longitude"
      name="longitude"
      value="{{ old('longitude', $etape->longitude ?? null) }}"
      placeholder="10.7376" />
    @error('longitude')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label for="temperature" class="form-label">Température</label>
    <div class="input-group">
      <input
        type="text"
        class="form-control @error('temperature') is-invalid @enderror"
        id="temperature"
        name="temperature"
        value="{{ old('temperature', $etape->temperature ?? null) }}"
        placeholder="4.5" />
      <span class="input-group-text">°C</span>
    </div>
    @error('temperature')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
      <div class="form-text">Facultative.</div>
    @enderror
  </div>

  <div class="col-md-8">
    <label for="description" class="form-label">Description</label>
    <textarea
      class="form-control @error('description') is-invalid @enderror"
      id="description"
      name="description"
      rows="3"
      placeholder="Détails de l'étape (facultatif)">{{ old('description', $etape->description ?? null) }}</textarea>
    @error('description')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mt-6">
  <button type="submit" class="btn btn-primary me-3">Enregistrer</button>
  <a href="{{ $etape->lot_id ? route('gestion.lots.show', $etape->lot_id) : route('gestion.etapes.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
