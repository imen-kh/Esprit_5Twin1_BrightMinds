<form method="POST" action="{{ route('admin.certifications.store') }}" enctype="multipart/form-data" class="row g-3">
    @csrf
    <div class="col-md-4">
        <label class="form-label" for="label_id">Label</label>
        <select name="label_id" id="label_id" class="form-select" required>
            <option value="">Choisir un label</option>
            @foreach ($labels as $label)
                <option value="{{ $label->id }}" @selected(old('label_id') == $label->id)>{{ $label->nom }}</option>
            @endforeach
        </select>
        @error('label_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="product_id">Produit</label>
        <select name="product_id" id="product_id" class="form-select" required>
            <option value="">Choisir un produit</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        @error('product_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="acteur_id">Acteur</label>
        <select name="acteur_id" id="acteur_id" class="form-select" required>
            <option value="">Choisir un acteur</option>
            @foreach ($acteurs as $acteur)
                <option value="{{ $acteur->id }}" @selected(old('acteur_id') == $acteur->id)>{{ $acteur->name }} ({{ $acteur->email }})</option>
            @endforeach
        </select>
        @error('acteur_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="numero">Numéro</label>
        <input name="numero" id="numero" class="form-control" value="{{ old('numero') }}" required>
        @error('numero')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="date_obtention">Date d'obtention</label>
        <input type="date" name="date_obtention" id="date_obtention" class="form-control" value="{{ old('date_obtention') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="date_expiration">Date d'expiration</label>
        <input type="date" name="date_expiration" id="date_expiration" class="form-control" value="{{ old('date_expiration') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="statut">Statut</label>
        <select name="statut" id="statut" class="form-select" required>
            @foreach (['en_attente' => 'En attente', 'validee' => 'Validée', 'refusee' => 'Refusée', 'expiree' => 'Expirée'] as $value => $label)
                <option value="{{ $value }}" @selected(old('statut', 'validee') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="fichier_pdf">Document PDF</label>
        <input type="file" name="fichier_pdf" id="fichier_pdf" class="form-control" accept="application/pdf" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="motif_refus">Motif de refus</label>
        <input name="motif_refus" id="motif_refus" class="form-control" value="{{ old('motif_refus') }}">
    </div>
    <div class="col-12"><button class="btn btn-primary">Créer la certification</button></div>
</form>
