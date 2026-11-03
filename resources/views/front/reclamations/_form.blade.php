@csrf
@if ($reclamation->exists)
    @method('PUT')
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="produit_id">Produit concerné <span class="text-danger">*</span></label>
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

    <div class="col-md-6">
        <label class="form-label" for="commande_id">Commande</label>
        <select class="form-select @error('commande_id') is-invalid @enderror" id="commande_id" name="commande_id">
            <option value="">Aucune commande précise</option>
            @foreach ($commandes as $commande)
                <option value="{{ $commande->id }}" data-produits="{{ $commande->products->pluck('id')->implode(',') }}"
                        @selected((string) old('commande_id', $reclamation->commande_id) === (string) $commande->id)>
                    #{{ $commande->id }} du {{ ($commande->date_commande ?? $commande->created_at)?->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($commande->products->pluck('name')->implode(', '), 45) }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Lier la commande accélère le traitement.</div>
        <div class="invalid-feedback">@error('commande_id'){{ $message }}@enderror</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="code_lot">Code du lot <span class="text-muted small">(facultatif)</span></label>
        <input class="form-control @error('code_lot') is-invalid @enderror" id="code_lot" name="code_lot" maxlength="40"
               value="{{ old('code_lot', $reclamation->lot?->code) }}" placeholder="Ex. LOT-2026-0001">
        <div class="form-text">Imprimé sur l'emballage : il permet de remonter toute la chaîne du produit.</div>
        <div class="invalid-feedback">@error('code_lot'){{ $message }}@enderror</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="priorite">Priorité ressentie <span class="text-danger">*</span></label>
        <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
            @foreach (\App\Models\Reclamation::prioritesLibelles() as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('priorite', $reclamation->priorite) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        <div class="form-text"><i class="fas fa-robot me-1"></i>L'IA relève la priorité si votre description évoque un risque pour la santé.</div>
        <div class="invalid-feedback">@error('priorite'){{ $message }}@enderror</div>
    </div>

    <div class="col-12">
        <label class="form-label" for="sujet">Sujet <span class="text-danger">*</span></label>
        <input class="form-control @error('sujet') is-invalid @enderror" id="sujet" name="sujet" value="{{ old('sujet', $reclamation->sujet) }}" required maxlength="255" placeholder="Ex. Oranges abîmées à la réception">
        <div class="invalid-feedback">@error('sujet'){{ $message }}@enderror</div>
    </div>

    <div class="col-12">
        <label class="form-label" for="description">Description <span class="text-danger">*</span></label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required maxlength="5000"
                  placeholder="Que s'est-il passé ? Quand ? Combien de produits sont concernés ? Vous pouvez écrire en français, en arabe, en anglais ou en derja.">{{ old('description', $reclamation->description) }}</textarea>
        <div class="invalid-feedback">@error('description'){{ $message }}@enderror</div>
    </div>
</div>

@push('scripts')
<script>
// Choisir une commande filtre la liste des produits sur ceux qu'elle contient
(() => {
    const commande = document.getElementById('commande_id');
    const produit = document.getElementById('produit_id');
    if (!commande || !produit) return;
    const filtrer = () => {
        const option = commande.selectedOptions[0];
        const ids = option && option.dataset.produits ? option.dataset.produits.split(',') : null;
        [...produit.options].forEach(o => { if (o.value) o.hidden = ids !== null && !ids.includes(o.value); });
        if (ids && produit.value && !ids.includes(produit.value)) produit.value = '';
        if (ids && ids.length === 1) produit.value = ids[0];
    };
    commande.addEventListener('change', filtrer);
    filtrer();
})();
</script>
@endpush
