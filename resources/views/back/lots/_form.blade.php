{{--
  Champs du formulaire d'un lot, partagés par la création et la modification.
  Variables : $lot (nouveau ou existant), $produits, $lotsOrigine,
  $origineFixe (lot à transformer transmis par « Transformer ce lot », sinon null).
--}}
<div class="row g-6">
  <div class="col-md-6">
    <label for="code" class="form-label">Code du lot</label>
    <input
      type="text"
      class="form-control @error('code') is-invalid @enderror"
      id="code"
      name="code"
      value="{{ old('code', $lot->code ?? null) }}"
      placeholder="LOT-2026-0001" />
    @error('code')
      <div class="invalid-feedback">{{ $message }}</div>
    @else
      <div class="form-text">Format : LOT-AAAA-NNNN.</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="produit_id" class="form-label">Produit</label>
    <select class="form-select @error('produit_id') is-invalid @enderror" id="produit_id" name="produit_id">
      <option value="">Choisissez un produit</option>
      @foreach ($produits as $produit)
        <option value="{{ $produit->id }}" @selected((string) old('produit_id', $lot->produit_id ?? null) === (string) $produit->id)>
          {{ $produit->name }}
        </option>
      @endforeach
    </select>
    @error('produit_id')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Lot d'origine : proposé seulement au transformateur et à l'admin (un lot de matière première n'en a pas) --}}
  @if ($origineFixe ?? null)
  {{-- Lot à transformer : affiché en lecture seule, transmis par un champ caché --}}
  <div class="col-12">
    <label class="form-label">Lot d'origine (matière première)</label>
    <input type="hidden" name="lot_origine_id" value="{{ $origineFixe->id }}" />
    <div class="border rounded p-4 bg-lighter @error('lot_origine_id') border-danger @enderror">
      <div class="d-flex flex-wrap align-items-center gap-4">
        <span class="badge bg-label-primary fs-6">{{ $origineFixe->code }}</span>
        <div>
          <strong>{{ $origineFixe->produit->name }}</strong>
          <small class="d-block text-body-secondary">
            Producteur : {{ $origineFixe->produit->user?->nom_entreprise ?: $origineFixe->produit->user?->name ?: '—' }}
            · produit le {{ $origineFixe->date_production->format('d/m/Y') }}
            · {{ number_format($origineFixe->quantite, 0, ',', ' ') }} {{ $origineFixe->unite }}
          </small>
        </div>
        <a href="{{ route('tracabilite.show', $origineFixe) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-auto">Voir sa traçabilité</a>
      </div>
    </div>
    @error('lot_origine_id')
      <div class="text-danger small mt-1">{{ $message }}</div>
    @else
      <div class="form-text">Le nouveau lot sera rattaché à ce lot de matière première.</div>
    @enderror
  </div>
  @elseif (\App\Models\Lot::origineAutoriseePour(auth()->user()))
  <div class="col-12">
    <label for="lot_origine_id" class="form-label">Lot d'origine (matière première)</label>
    <select class="form-select @error('lot_origine_id') is-invalid @enderror" id="lot_origine_id" name="lot_origine_id">
      <option value="">Aucun : ce lot n'est pas issu d'un autre lot</option>
      @foreach ($lotsOrigine as $origine)
        <option value="{{ $origine->id }}" @selected((string) old('lot_origine_id', $lot->lot_origine_id ?? null) === (string) $origine->id)>
          {{ $origine->code }} — {{ $origine->produit->name }}, {{ $origine->produit->user?->nom_entreprise ?: $origine->produit->user?->name }} (produit le {{ $origine->date_production->format('d/m/Y') }})
        </option>
      @endforeach
    </select>
    @error('lot_origine_id')
      <div class="invalid-feedback">{{ $message }}</div>
    @else
      <div class="form-text">
        Facultatif. À renseigner pour un lot transformé, par exemple une confiture issue d'un lot de fraises.
        @unless (auth()->user()->isAdmin()) Seuls les lots des matières premières que vous avez commandées sont proposés. @endunless
      </div>
    @enderror
  </div>
  @endif

  <div class="col-md-6">
    <label for="date_production" class="form-label">Date de production</label>
    <input
      type="date"
      class="form-control @error('date_production') is-invalid @enderror"
      id="date_production"
      name="date_production"
      value="{{ old('date_production', $lot->date_production?->format('Y-m-d')) }}" />
    @error('date_production')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="date_peremption" class="form-label">Date de péremption</label>
    <input
      type="date"
      class="form-control @error('date_peremption') is-invalid @enderror"
      id="date_peremption"
      name="date_peremption"
      value="{{ old('date_peremption', $lot->date_peremption?->format('Y-m-d')) }}" />
    @error('date_peremption')
      <div class="invalid-feedback">{{ $message }}</div>
    @else
      <div class="form-text">Facultative.</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label for="quantite" class="form-label">Quantité</label>
    <input
      type="number"
      class="form-control @error('quantite') is-invalid @enderror"
      id="quantite"
      name="quantite"
      value="{{ old('quantite', $lot->quantite ?? null) }}"
      min="1"
      placeholder="500" />
    @error('quantite')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label for="unite" class="form-label">Unité</label>
    <select class="form-select @error('unite') is-invalid @enderror" id="unite" name="unite">
      <option value="">Choisissez une unité</option>
      @foreach (\App\Models\Lot::UNITES as $unite)
        <option value="{{ $unite }}" @selected(old('unite', $lot->unite ?? null) === $unite)>{{ $unite }}</option>
      @endforeach
    </select>
    @error('unite')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label for="statut" class="form-label">Statut</label>
    <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut">
      <option value="">Choisissez un statut</option>
      @foreach (\App\Models\Lot::STATUTS as $cle => $infos)
        <option value="{{ $cle }}" @selected(old('statut', $lot->statut ?? null) === $cle)>{{ $infos['label'] }}</option>
      @endforeach
    </select>
    @error('statut')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label for="notes" class="form-label">Notes</label>
    <textarea
      class="form-control @error('notes') is-invalid @enderror"
      id="notes"
      name="notes"
      rows="3"
      placeholder="Informations complémentaires sur le lot (facultatif)">{{ old('notes', $lot->notes ?? null) }}</textarea>
    @error('notes')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="mt-6">
  <button type="submit" class="btn btn-primary me-3">Enregistrer</button>
  <a href="{{ $lot->exists ? route('gestion.lots.show', $lot) : route('gestion.lots.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
