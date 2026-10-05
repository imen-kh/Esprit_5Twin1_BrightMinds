@extends('layouts.front')

@section('title', $product->exists ? 'Modifier un produit' : 'Ajouter un produit')

@section('content')
@include('partials.front.page-header', ['titre' => $product->exists ? 'Modifier un produit' : 'Ajouter un produit'])

<div class="container py-4">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-7">
            <div class="bg-light rounded p-3 p-md-4">
                <h2 class="h4 text-primary mb-1">{{ $product->exists ? 'Modifier le produit' : 'Nouveau produit' }}</h2>
                <p class="mb-3">Dès l’enregistrement, le produit est publié et visible dans le catalogue.</p>

                <form id="product-form" method="POST" action="{{ $product->exists ? route('acteur.produits.update', $product) : route('acteur.produits.store') }}" enctype="multipart/form-data" data-has-image="{{ $product->image ? '1' : '0' }}" novalidate>
                    @csrf
                    @if ($product->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control border-secondary rounded-pill py-2 px-3 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="Ex. Tomate">
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
                            <textarea class="form-control border-secondary rounded py-2 px-3 @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Décrivez le produit">{{ old('description', $product->description) }}</textarea>
                            <div class="form-text">Entre 10 et 1000 caractères.</div>
                            <div class="invalid-feedback" data-error-for="description">@error('description'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="price">Prix (DT)</label>
                            <input class="form-control border-secondary rounded-pill py-2 px-3 @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" placeholder="Ex. 2,500">
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
                            <label class="form-label" for="image">Image</label>
                            <input class="form-control form-control-sm border-secondary @error('image') is-invalid @enderror" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG ou WEBP, 2 Mo maximum.@if ($product->image) Laissez vide pour conserver l’image actuelle.@endif</div>
                            <div class="invalid-feedback" data-error-for="image">@error('image'){{ $message }}@enderror</div>
                            <img id="image-preview" class="d-none rounded mt-2" alt="Aperçu de l’image" style="max-height: 120px;">
                            @if ($product->image)
                                <img id="current-image" class="rounded mt-2" src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" style="max-height: 120px;">
                            @endif
                        </div>
                        @if (auth()->user()->type_acteur === 'transformateur')
                            <div class="col-12">
                                <label class="form-label" for="ingredients">Ingrédients</label>
                                <textarea class="form-control border-secondary rounded py-2 px-3 @error('ingredients') is-invalid @enderror" id="ingredients" name="ingredients" rows="3">{{ old('ingredients', $product->ingredients) }}</textarea>
                                <div class="form-text">Matières premières transformées, entre 10 et 1000 caractères. Exemple : fraises, sucre, jus de citron.</div>
                                <div class="invalid-feedback" data-error-for="ingredients">@error('ingredients'){{ $message }}@enderror</div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-primary rounded-pill px-4" type="submit">Enregistrer</button>
                        <a class="btn border border-secondary rounded-pill px-4 text-primary" href="{{ route('acteur.produits.index') }}">Annuler</a>
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
