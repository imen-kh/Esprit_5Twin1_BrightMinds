{{--
  Champs du formulaire d'une livraison, partagés par la création et la modification.
  Variables : $commande (commande livrée) et $livraison (null à la création).
  Chaque champ : is-invalid + message @error + valeur conservée avec old().
--}}
<div class="col-md-8">
    <label for="adresse_livraison" class="form-label">Adresse de livraison <span class="text-danger">*</span></label>
    <input
        type="text"
        id="adresse_livraison"
        name="adresse_livraison"
        class="form-control @error('adresse_livraison') is-invalid @enderror"
        value="{{ old('adresse_livraison', $livraison->adresse_livraison ?? $commande->adresse ?? '') }}"
        placeholder="Ex. 12 rue de la Liberté"
        maxlength="255">
    @error('adresse_livraison')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-4">
    <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
    <input
        type="text"
        id="ville"
        name="ville"
        class="form-control @error('ville') is-invalid @enderror"
        value="{{ old('ville', $livraison->ville ?? $commande->ville ?? '') }}"
        placeholder="Ex. Sousse"
        maxlength="100">
    @error('ville')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-4">
    <label for="code_postal" class="form-label">Code postal <span class="text-danger">*</span></label>
    <input
        type="text"
        id="code_postal"
        name="code_postal"
        class="form-control @error('code_postal') is-invalid @enderror"
        value="{{ old('code_postal', $livraison->code_postal ?? $commande->code_postal ?? '') }}"
        placeholder="4 chiffres, ex. 4000"
        inputmode="numeric"
        maxlength="4">
    @error('code_postal')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-4">
    <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
    <input
        type="tel"
        id="telephone"
        name="telephone"
        class="form-control @error('telephone') is-invalid @enderror"
        value="{{ old('telephone', $livraison->telephone ?? $commande->telephone ?? '') }}"
        placeholder="8 chiffres, ex. 22123456"
        inputmode="numeric"
        maxlength="8">
    @error('telephone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-4">
    <label for="numero_suivi" class="form-label">N° de suivi <span class="text-danger">*</span></label>
    {{-- À la création, un numéro est proposé à partir de la commande : LIV-AAAA-NNNN --}}
    <input
        type="text"
        id="numero_suivi"
        name="numero_suivi"
        class="form-control @error('numero_suivi') is-invalid @enderror"
        value="{{ old('numero_suivi', $livraison->numero_suivi ?? 'LIV-'.date('Y').'-'.str_pad((string) ($commande->id ?? 0), 4, '0', STR_PAD_LEFT)) }}"
        placeholder="Ex. LIV-2026-0001"
        maxlength="30">
    @error('numero_suivi')
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        <div class="form-text">Lettres, chiffres et tirets.</div>
    @enderror
</div>

<div class="col-md-6">
    <label for="date_livraison_prevue" class="form-label">Date de livraison prévue <span class="text-danger">*</span></label>
    <input
        type="date"
        id="date_livraison_prevue"
        name="date_livraison_prevue"
        class="form-control @error('date_livraison_prevue') is-invalid @enderror"
        value="{{ old('date_livraison_prevue', isset($livraison->date_livraison_prevue) ? $livraison->date_livraison_prevue->format('Y-m-d') : '') }}"
        min="{{ ($livraison->created_at ?? now())->toDateString() }}"
        max="{{ now()->addMonths(3)->toDateString() }}">
    @error('date_livraison_prevue')
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        <div class="form-text">Au plus tard dans 3 mois.</div>
    @enderror
</div>

<div class="col-12">
    <label for="commentaire" class="form-label">Commentaire</label>
    <textarea
        id="commentaire"
        name="commentaire"
        class="form-control @error('commentaire') is-invalid @enderror"
        rows="3"
        maxlength="1000"
        placeholder="Instructions pour le livreur (facultatif)">{{ old('commentaire', $livraison->commentaire ?? '') }}</textarea>
    @error('commentaire')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
