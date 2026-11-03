{{-- Champs d'un avis : étoiles, titre, commentaire (création et modification) --}}
@php $noteCourante = (int) old('note', $avisCourant?->note); @endphp
<div class="mb-3">
    <label class="form-label d-block">Votre note <span class="text-danger">*</span></label>
    <div class="ra-choix-etoiles" role="radiogroup" aria-label="Note sur 5">
        @for ($n = 5; $n >= 1; $n--)
            <input type="radio" id="note-{{ $n }}" name="note" value="{{ $n }}" @checked($noteCourante === $n) required>
            <label for="note-{{ $n }}" title="{{ $n }} étoile{{ $n > 1 ? 's' : '' }}"><i class="fas fa-star"></i></label>
        @endfor
    </div>
    @error('note')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="titre">Titre <span class="text-muted small">(facultatif)</span></label>
    <input type="text" id="titre" name="titre" maxlength="120" class="form-control @error('titre') is-invalid @enderror"
           value="{{ old('titre', $avisCourant?->titre) }}" placeholder="Ex. Des oranges juteuses et sucrées">
    @error('titre')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="commentaire">Votre avis <span class="text-danger">*</span></label>
    <textarea id="commentaire" name="commentaire" rows="4" maxlength="2000" required
              class="form-control @error('commentaire') is-invalid @enderror"
              placeholder="Goût, fraîcheur, emballage, livraison… racontez-nous.">{{ old('commentaire', $avisCourant?->commentaire) }}</textarea>
    @error('commentaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
