@extends('layouts.front')

@php
    // Le transformateur saisit un produit transformé (avec ses ingrédients), le producteur une matière première
    $transformateur = auth()->user()->isTransformateur();
    $titre = $product->exists
        ? ($transformateur ? 'Modifier un produit transformé' : 'Modifier un produit')
        : ($transformateur ? 'Ajouter un produit transformé' : 'Ajouter un produit');
@endphp

@section('title', $titre)

@section('content')
@include('partials.front.page-header', ['titre' => $titre])

<div class="container py-4">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-7">
            <div class="bg-light rounded p-3 p-md-4">
                <h2 class="h4 text-primary mb-1">{{ $product->exists ? ($transformateur ? 'Modifier le produit transformé' : 'Modifier le produit') : ($transformateur ? 'Nouveau produit transformé' : 'Nouveau produit') }}</h2>
                <p class="mb-3">Après l’enregistrement, le produit reste en attente. Il n’apparaît dans le catalogue qu’une fois approuvé par un administrateur.</p>

                @if ($product->exists && filled($product->review_note))
                    <div class="alert alert-warning">
                        <strong>{{ $product->libelleStatut() }}.</strong> {{ $product->review_note }}
                    </div>
                @endif

                <form id="product-form" method="POST" action="{{ $product->exists ? route('acteur.produits.update', $product) : route('acteur.produits.store') }}" enctype="multipart/form-data" data-has-image="{{ $product->aUnePhoto() ? '1' : '0' }}" data-url-ia="{{ route('acteur.produits.generer-ia') }}" data-url-qualite="{{ route('acteur.produits.qualite-ia') }}" novalidate>
                    @csrf
                    @if ($product->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control border-secondary rounded-pill py-2 px-3 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="{{ $transformateur ? 'Ex. Confiture de fraises' : 'Ex. Tomate' }}">
                            <div class="form-text">Entre 3 et 100 caractères.</div>
                            <div class="invalid-feedback" data-error-for="name">@error('name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Catégorie</label>
                            <select class="form-select border-secondary rounded-pill py-2 px-3 @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                <option value="">Choisir une catégorie</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="category_id">@error('category_id'){{ $message }}@enderror</div>
                            <button class="btn btn-link text-primary px-0" type="button" id="toggle-category-suggestion">Suggérer une catégorie</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control border-secondary rounded py-2 px-3 @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="{{ $transformateur ? 'Décrivez le produit transformé' : 'Décrivez le produit' }}">{{ old('description', $product->description) }}</textarea>
                            <div class="form-text">Entre 10 et 1000 caractères.</div>
                            <div class="invalid-feedback" data-error-for="description">@error('description'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="price">Prix (DT)</label>
                            <input class="form-control border-secondary rounded-pill py-2 px-3 @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" placeholder="{{ $transformateur ? 'Ex. 8,500' : 'Ex. 2,500' }}">
                            <div class="form-text">Entre 0,1 et 9999 DT.</div>
                            <div class="invalid-feedback" data-error-for="price">@error('price'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="origin">Origine</label>
                            <input class="form-control border-secondary rounded-pill py-2 px-3 @error('origin') is-invalid @enderror" id="origin" name="origin" value="{{ old('origin', $product->origin) }}" placeholder="Ex. Nabeul">
                            <div class="form-text">Entre 3 et 150 caractères.</div>
                            <div class="invalid-feedback" data-error-for="origin">@error('origin'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <div id="assistant-produit" class="alert d-none mb-0" role="status"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="image">Image</label>
                            <input class="form-control form-control-sm border-secondary @error('image') is-invalid @enderror" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG ou WEBP, 2 Mo maximum.@if ($product->aUnePhoto()) Laissez vide pour conserver l’image actuelle.@endif</div>
                            <div class="invalid-feedback" data-error-for="image">@error('image'){{ $message }}@enderror</div>
                            <img id="image-preview" class="d-none rounded mt-2" alt="Aperçu de l’image" style="max-height: 120px;">
                            @if ($product->aUnePhoto())
                                <img id="current-image" class="rounded mt-2" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" style="max-height: 120px;">
                            @endif
                        </div>
                        @if ($transformateur)
                            <div class="col-12">
                                <label class="form-label" for="ingredients">Ingrédients</label>
                                <textarea class="form-control border-secondary rounded py-2 px-3 @error('ingredients') is-invalid @enderror" id="ingredients" name="ingredients" rows="3" placeholder="Ex. fraises, sucre, jus de citron">{{ old('ingredients', $product->ingredients) }}</textarea>
                                <div class="form-text">Matières premières transformées, entre 10 et 1000 caractères. Exemple : fraises, sucre, jus de citron.</div>
                                <div class="invalid-feedback" data-error-for="ingredients">@error('ingredients'){{ $message }}@enderror</div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn border border-secondary rounded-pill px-4 text-primary" type="button" id="generer-ia">Générer avec IA</button>
                        <button class="btn border border-secondary rounded-pill px-4 text-primary" type="button" id="qualite-ia">Analyser la qualité avec IA</button>
                        <button class="btn btn-primary rounded-pill px-4" type="submit">Enregistrer</button>
                        <a class="btn border border-secondary rounded-pill px-4 text-primary" href="{{ route('acteur.produits.index') }}">Annuler</a>
                    </div>
                    <div id="carte-qualite" class="mt-3">
                        @if ($product->quality_analysis)
                            @include('partials.qualite-produit', ['analyse' => $product->quality_analysis])
                        @endif
                    </div>
                </form>
            </div>

            <form id="category-suggestion" class="bg-white border rounded p-3 p-md-4 mt-3 {{ $errors->has('category_name') || $errors->has('category_description') ? '' : 'd-none' }}" method="POST" action="{{ route('acteur.categories.suggerer') }}" novalidate>
                @csrf
                <h2 class="h5 text-primary">Suggérer une catégorie</h2>
                <p class="mb-3">Elle reste en attente jusqu’à la validation d’un administrateur.</p>
                <div class="mb-3">
                    <label class="form-label" for="category_name">Nom</label>
                    <input class="form-control border-secondary rounded-pill py-2 px-3 @error('category_name') is-invalid @enderror" id="category_name" name="category_name" value="{{ old('category_name') }}" placeholder="Ex. Agrumes">
                    <div class="form-text">Entre 3 et 50 caractères.</div>
                    <div class="invalid-feedback">@error('category_name'){{ $message }}@enderror</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="category_description">Description</label>
                    <textarea class="form-control border-secondary rounded py-2 px-3 @error('category_description') is-invalid @enderror" id="category_description" name="category_description" rows="3" placeholder="Décrivez la catégorie">{{ old('category_description') }}</textarea>
                    <div class="form-text">Entre 10 et 200 caractères.</div>
                    <div class="invalid-feedback">@error('category_description'){{ $message }}@enderror</div>
                </div>
                <button class="btn btn-primary rounded-pill px-4" type="submit">Envoyer la suggestion</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('product-form').addEventListener('submit', function (event) {
    const form = event.currentTarget;
    let valid = true;

    form.querySelectorAll('[data-error-for]').forEach(function (message) {
        message.textContent = '';
        form.querySelector('[name="' + message.getAttribute('data-error-for') + '"]').classList.remove('is-invalid');
    });

    function fail(field, message) {
        const input = form.querySelector('[name="' + field + '"]');
        input.classList.add('is-invalid');
        form.querySelector('[data-error-for="' + field + '"]').textContent = message;
        valid = false;
    }

    const name = form.name.value.trim();
    if (name === '') {
        fail('name', 'Le nom du produit est obligatoire.');
    } else if (name.length < 3) {
        fail('name', 'Le nom doit contenir au moins 3 caractères.');
    } else if (name.length > 100) {
        fail('name', 'Le nom ne peut pas dépasser 100 caractères.');
    }

    if (form.category_id.value === '') {
        fail('category_id', 'La catégorie est obligatoire.');
    }

    const description = form.description.value.trim();
    if (description === '') {
        fail('description', 'La description est obligatoire.');
    } else if (description.length < 10) {
        fail('description', 'La description doit contenir au moins 10 caractères.');
    } else if (description.length > 1000) {
        fail('description', 'La description ne peut pas dépasser 1000 caractères.');
    }

    const priceText = form.price.value.trim().replace(',', '.');
    const price = Number(priceText);
    if (priceText === '') {
        fail('price', 'Le prix est obligatoire.');
    } else if (!/^\d+(\.\d+)?$/.test(priceText)) {
        fail('price', 'Le prix doit être un nombre.');
    } else if (price < 0.1) {
        fail('price', 'Le prix doit être au moins 0,1 DT.');
    } else if (price > 9999) {
        fail('price', 'Le prix ne peut pas dépasser 9999 DT.');
    }

    const origin = form.origin.value.trim();
    if (origin === '') {
        fail('origin', 'L\'origine est obligatoire.');
    } else if (origin.length < 3) {
        fail('origin', 'L\'origine doit contenir au moins 3 caractères.');
    } else if (origin.length > 150) {
        fail('origin', 'L\'origine ne peut pas dépasser 150 caractères.');
    }

    const image = form.image.files[0];
    if (!image && form.dataset.hasImage !== '1') {
        fail('image', 'L\'image du produit est obligatoire.');
    } else if (image) {
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(image.type)) {
            fail('image', 'L\'image doit être au format JPG, PNG ou WEBP.');
        } else if (image.size > 2 * 1024 * 1024) {
            fail('image', 'L\'image ne peut pas dépasser 2 Mo.');
        }
    }

    if (form.ingredients) {
        const ingredients = form.ingredients.value.trim();
        if (ingredients === '') {
            fail('ingredients', 'Les ingrédients sont obligatoires.');
        } else if (ingredients.length < 10) {
            fail('ingredients', 'Les ingrédients doivent contenir au moins 10 caractères.');
        } else if (ingredients.length > 1000) {
            fail('ingredients', 'Les ingrédients ne peuvent pas dépasser 1000 caractères.');
        }
    }

    if (!valid) {
        event.preventDefault();
    }
});

document.getElementById('image').addEventListener('change', function () {
    const preview = document.getElementById('image-preview');
    const current = document.getElementById('current-image');
    const file = this.files[0];
    if (!file) {
        preview.classList.add('d-none');
        if (current) current.classList.remove('d-none');
        return;
    }
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('d-none');
    if (current) current.classList.add('d-none');
});

const form = document.getElementById('product-form');
const assistant = document.getElementById('assistant-produit');
const jeton = form.querySelector('input[name="_token"]').value;

function messageAssistant(texte, type) {
    assistant.className = 'alert alert-' + type + ' mb-0';
    assistant.textContent = texte;
}

function remplirSiPresent(champ, valeur) {
    if (!champ || valeur === null || valeur === undefined || valeur === '') {
        return;
    }
    champ.value = valeur;
}

async function appelerAssistant(url, options) {
    messageAssistant('Recherche en cours…', 'secondary');
    try {
        const reponse = await fetch(url, options);
        const donnees = await reponse.json().catch(function () { return {}; });
        if (!reponse.ok) {
            messageAssistant(donnees.message || 'Service indisponible. Complétez la fiche manuellement.', 'warning');
            return;
        }
        remplirSiPresent(form.name, donnees.name);
        remplirSiPresent(form.description, donnees.description);
        remplirSiPresent(form.origin, donnees.origin);
        remplirSiPresent(form.ingredients, donnees.ingredients);
        if (donnees.category_id && form.category_id.querySelector('option[value="' + donnees.category_id + '"]')) {
            form.category_id.value = String(donnees.category_id);
        }
        const detail = donnees.category_name ? ' Catégorie proposée : ' + donnees.category_name + '.' : '';
        messageAssistant('Proposition reçue. Relisez les champs avant d’enregistrer.' + detail, 'success');
    } catch (erreur) {
        messageAssistant('Service indisponible. Complétez la fiche manuellement.', 'warning');
    }
}

document.getElementById('generer-ia').addEventListener('click', function () {
    const image = form.image.files[0];
    if (!image) {
        messageAssistant('Choisissez d’abord une image, puis relancez la génération.', 'warning');
        return;
    }
    const envoi = new FormData();
    envoi.append('image', image);
    envoi.append('_token', jeton);
    appelerAssistant(form.dataset.urlIa, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': jeton },
        body: envoi
    });
});

document.getElementById('qualite-ia').addEventListener('click', async function () {
    const image = form.image.files[0];
    const carte = document.getElementById('carte-qualite');
    if (!image) {
        messageAssistant('Choisissez d’abord une photo du fruit ou du légume.', 'warning');
        return;
    }
    messageAssistant('Analyse visuelle en cours…', 'secondary');
    const envoi = new FormData();
    envoi.append('image', image);
    envoi.append('_token', jeton);
    try {
        const reponse = await fetch(form.dataset.urlQualite, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': jeton },
            body: envoi
        });
        const donnees = await reponse.json().catch(function () { return {}; });
        if (!reponse.ok) {
            messageAssistant(donnees.message || 'Analyse indisponible. Vous pouvez enregistrer le produit sans elle.', 'warning');
            return;
        }
        carte.replaceChildren(carteQualite(donnees));
        messageAssistant('Analyse indicative affichée. Elle sera jointe au produit si vous enregistrez cette même photo.', 'success');
    } catch (erreur) {
        messageAssistant('Analyse indisponible. Vous pouvez enregistrer le produit sans elle.', 'warning');
    }
});

function carteQualite(donnees) {
    const carte = document.createElement('div');
    carte.className = 'card border-0 shadow-sm';
    const corps = document.createElement('div');
    corps.className = 'card-body';
    const entete = document.createElement('div');
    entete.className = 'd-flex justify-content-between align-items-start gap-3 mb-3';
    const titres = document.createElement('div');
    const titre = document.createElement('h3');
    titre.className = 'h5 mb-1';
    titre.textContent = 'Qualité visuelle';
    const avertissement = document.createElement('p');
    avertissement.className = 'small text-body-secondary mb-0';
    avertissement.textContent = donnees.avertissement || 'Indication visuelle seulement.';
    titres.append(titre, avertissement);
    entete.append(titres);
    if (donnees.evaluable) {
        const score = document.createElement('div');
        score.className = 'text-center flex-shrink-0';
        const note = document.createElement('div');
        note.className = 'display-6 fw-bold text-primary lh-1';
        note.textContent = donnees.score;
        const sur = document.createElement('div');
        sur.className = 'small text-body-secondary';
        sur.textContent = '/ 100';
        const badge = document.createElement('span');
        const classes = { 'Excellente': 'bg-success', 'Bonne': 'bg-primary', 'Moyenne': 'bg-warning text-dark', 'Faible': 'bg-danger' };
        badge.className = 'badge mt-1 ' + (classes[donnees.classification] || 'bg-secondary');
        badge.textContent = donnees.classification;
        score.append(note, sur, badge);
        entete.append(score);
    } else {
        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary';
        badge.textContent = 'Photo insuffisante';
        entete.append(badge);
    }
    corps.append(entete);
    if (donnees.evaluable) {
        corps.append(listeQualite('Défauts visibles', donnees.defauts, 'Aucun défaut visible sur cette photo.'));
    }
    if (donnees.recommandations && donnees.recommandations.length) {
        corps.append(listeQualite('Tri et présentation', donnees.recommandations, ''));
    }
    carte.append(corps);
    return carte;
}

function listeQualite(titreTexte, lignes, vide) {
    const bloc = document.createDocumentFragment();
    const titre = document.createElement('p');
    titre.className = 'fw-medium mb-1';
    titre.textContent = titreTexte;
    bloc.append(titre);
    if (!lignes || !lignes.length) {
        const texte = document.createElement('p');
        texte.className = 'text-body-secondary';
        texte.textContent = vide;
        bloc.append(texte);
        return bloc;
    }
    const liste = document.createElement('ul');
    liste.className = 'mb-3';
    lignes.forEach(function (ligne) {
        const item = document.createElement('li');
        item.textContent = ligne;
        liste.append(item);
    });
    bloc.append(liste);
    return bloc;
}

document.getElementById('toggle-category-suggestion').addEventListener('click', function () {
    document.getElementById('category-suggestion').classList.toggle('d-none');
});

document.getElementById('category-suggestion').addEventListener('submit', function (event) {
    const form = event.currentTarget;
    let valid = true;

    function fail(field, message) {
        const input = form.querySelector('[name="' + field + '"]');
        input.classList.add('is-invalid');
        input.parentElement.querySelector('.invalid-feedback').textContent = message;
        valid = false;
    }

    form.querySelectorAll('.is-invalid').forEach(function (input) {
        input.classList.remove('is-invalid');
    });

    const name = form.category_name.value.trim();
    if (name === '') {
        fail('category_name', 'Le nom de la catégorie est obligatoire.');
    } else if (name.length < 3) {
        fail('category_name', 'Le nom doit contenir au moins 3 caractères.');
    } else if (name.length > 50) {
        fail('category_name', 'Le nom ne peut pas dépasser 50 caractères.');
    }

    const description = form.category_description.value.trim();
    if (description === '') {
        fail('category_description', 'La description est obligatoire.');
    } else if (description.length < 10) {
        fail('category_description', 'La description doit contenir au moins 10 caractères.');
    } else if (description.length > 200) {
        fail('category_description', 'La description ne peut pas dépasser 200 caractères.');
    }

    if (!valid) {
        event.preventDefault();
    }
});
</script>
@endpush
