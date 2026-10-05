<form method="POST" action="{{ isset($label) ? route('admin.labels.update',$label) : route('admin.labels.store') }}" enctype="multipart/form-data">
@csrf @isset($label) @method('PUT') @endisset
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nom</label><input name="nom" class="form-control" value="{{ old('nom',$label->nom ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Organisme</label><input name="organisme" class="form-control" value="{{ old('organisme',$label->organisme ?? '') }}" required></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" required>{{ old('description',$label->description ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" {{ isset($label) ? '' : 'required' }}></div>
<div class="col-md-3"><label class="form-label">Validité (mois)</label><input type="number" name="duree_validite_mois" class="form-control" value="{{ old('duree_validite_mois',$label->duree_validite_mois ?? 12) }}" min="1" required></div>
<div class="col-md-3 form-check mt-5 ms-3"><input type="checkbox" name="actif" value="1" class="form-check-input" {{ old('actif',$label->actif ?? true) ? 'checked' : '' }}><label class="form-check-label">Actif</label></div></div>
<button class="btn btn-primary mt-4">Enregistrer</button></form>
