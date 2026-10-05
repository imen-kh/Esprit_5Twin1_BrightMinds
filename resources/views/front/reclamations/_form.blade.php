@csrf
@if ($reclamation->exists)
    @method('PUT')
@endif

<div class="mb-3">
    <label class="form-label" for="produit_id">Produit</label>
    <select class="form-select @error('produit_id') is-invalid @enderror" id="produit_id" name="produit_id" required>
        <option value="">Sélectionner un produit</option>
        @foreach ($products as $product)
            <option value="{{ $product->id }}" @selected((string) old('produit_id', $reclamation->produit_id) === (string) $product->id)>
                {{ $product->name }}
            </option>
        @endforeach
    </select>
    <div class="invalid-feedback">@error('produit_id'){{ $message }}@enderror</div>
</div>

<div class="mb-3">
    <label class="form-label" for="commande_id">Numéro de commande</label>
    <input class="form-control @error('commande_id') is-invalid @enderror" id="commande_id" name="commande_id" type="number" min="1" value="{{ old('commande_id', $reclamation->commande_id) }}" placeholder="Facultatif">
    <div class="form-text">Laissez vide si la réclamation ne concerne pas une commande.</div>
    <div class="invalid-feedback">@error('commande_id'){{ $message }}@enderror</div>
</div>

<div class="mb-3">
    <label class="form-label" for="sujet">Sujet</label>
    <input class="form-control @error('sujet') is-invalid @enderror" id="sujet" name="sujet" value="{{ old('sujet', $reclamation->sujet) }}" required maxlength="255">
    <div class="invalid-feedback">@error('sujet'){{ $message }}@enderror</div>
</div>

<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description', $reclamation->description) }}</textarea>
    <div class="invalid-feedback">@error('description'){{ $message }}@enderror</div>
</div>

<div class="mb-3">
    <label class="form-label" for="priorite">Priorité</label>
    <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
        @foreach (['faible' => 'Faible', 'moyenne' => 'Moyenne', 'haute' => 'Haute', 'urgente' => 'Urgente'] as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected(old('priorite', $reclamation->priorite) === $valeur)>{{ $libelle }}</option>
        @endforeach
    </select>
    <div class="invalid-feedback">@error('priorite'){{ $message }}@enderror</div>
</div>
