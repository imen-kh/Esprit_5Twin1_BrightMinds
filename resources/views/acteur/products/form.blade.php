@extends('layouts.front')

@section('title', $product->exists ? 'Modifier un produit' : 'Nouveau produit')

@section('content')
@include('partials.front.page-header', ['titre' => $product->exists ? 'Modifier un produit' : 'Nouveau produit'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form id="product-form" class="border rounded p-4" method="POST" action="{{ $product->exists ? route('acteur.produits.update', $product) : route('acteur.produits.store') }}" novalidate>
        @csrf
        @if ($product->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label" for="name">Nom</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}">
            <div class="form-text">Entre 3 et 100 caractères.</div>
            <div class="invalid-feedback" data-error-for="name">@error('name'){{ $message }}@enderror</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="category_id">Catégorie</label>
            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                <option value="">Choisir une catégorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="category_id">@error('category_id'){{ $message }}@enderror</div>
            <button class="btn btn-link px-0" type="button" id="toggle-category-suggestion">Suggérer une catégorie</button>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
            <div class="form-text">Entre 10 et 1000 caractères.</div>
            <div class="invalid-feedback" data-error-for="description">@error('description'){{ $message }}@enderror</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="price">Prix (DT)</label>
            <input class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}">
            <div class="form-text">Entre 0,1 et 9999 DT.</div>
            <div class="invalid-feedback" data-error-for="price">@error('price'){{ $message }}@enderror</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="origin">Origine</label>
            <input class="form-control @error('origin') is-invalid @enderror" id="origin" name="origin" value="{{ old('origin', $product->origin) }}">
            <div class="form-text">Entre 3 et 150 caractères.</div>
            <div class="invalid-feedback" data-error-for="origin">@error('origin'){{ $message }}@enderror</div>
        </div>
        @if (auth()->user()->type_acteur === 'transformateur')
            <div class="mb-3">
                <label class="form-label" for="ingredients">Ingrédients</label>
                <textarea class="form-control @error('ingredients') is-invalid @enderror" id="ingredients" name="ingredients" rows="3">{{ old('ingredients', $product->ingredients) }}</textarea>
                <div class="form-text">Matières premières transformées, entre 10 et 1000 caractères. Exemple : fraises, sucre, jus de citron.</div>
                <div class="invalid-feedback" data-error-for="ingredients">@error('ingredients'){{ $message }}@enderror</div>
            </div>
        @endif
        <button class="btn btn-primary">Enregistrer</button>
        <a class="btn btn-secondary" href="{{ route('acteur.produits.index') }}">Annuler</a>
    </form>
    <form id="category-suggestion" class="border rounded p-4 mt-3 {{ $errors->has('category_name') || $errors->has('category_description') ? '' : 'd-none' }}" method="POST" action="{{ route('acteur.categories.suggerer') }}" novalidate>
        @csrf
        <h2 class="h5">Suggérer une catégorie</h2>
        <p class="text-muted">Elle restera en attente jusqu'à la validation d'un administrateur.</p>
        <div class="mb-3">
            <label class="form-label" for="category_name">Nom</label>
            <input class="form-control @error('category_name') is-invalid @enderror" id="category_name" name="category_name" value="{{ old('category_name') }}">
            <div class="form-text">Entre 3 et 50 caractères.</div>
            <div class="invalid-feedback">@error('category_name'){{ $message }}@enderror</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="category_description">Description</label>
            <textarea class="form-control @error('category_description') is-invalid @enderror" id="category_description" name="category_description" rows="3">{{ old('category_description') }}</textarea>
            <div class="form-text">Entre 10 et 200 caractères.</div>
            <div class="invalid-feedback">@error('category_description'){{ $message }}@enderror</div>
        </div>
        <button class="btn btn-primary">Envoyer la suggestion</button>
    </form>
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
