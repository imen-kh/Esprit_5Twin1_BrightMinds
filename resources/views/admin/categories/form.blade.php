@extends('layouts.back')

@section('title', $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie' }}</h4>
        <p class="mb-0 text-body-secondary">
            @if ($category->exists && $category->origin === \App\Models\Category::ORIGIN_PRODUCER)
                Proposition d’un producteur. La validation se fait depuis la liste des catégories.
            @else
                Une catégorie créée ici est validée tout de suite et visible dans le catalogue.
            @endif
        </p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Retour à la liste</a>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-6">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informations</h5>
                @if ($category->exists)
                    <span class="badge bg-label-{{ $category->origin === \App\Models\Category::ORIGIN_PRODUCER ? 'info' : 'primary' }}">
                        {{ $category->origin === \App\Models\Category::ORIGIN_PRODUCER ? 'Producteur' : 'Admin' }}
                    </span>
                @endif
            </div>
            <div class="card-body">
                <form id="category-form" method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" novalidate>
                    @csrf
                    @if ($category->exists) @method('PUT') @endif

                    <div class="mb-6">
                        <label class="form-label" for="name">Nom</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-purchase-tag"></i></span>
                            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" maxlength="50" value="{{ old('name', $category->name) }}" placeholder="Ex. Légumes">
                        </div>
                        <div class="form-text" data-counter="name">3 à 50 caractères.</div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" maxlength="200" placeholder="Décrivez ce que regroupe cette catégorie.">{{ old('description', $category->description) }}</textarea>
                        <div class="form-text" data-counter="description">10 à 200 caractères.</div>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('category-form').addEventListener('submit', function (event) {
    const form = event.currentTarget;
    let valid = true;

    function show(name, message) {
        const field = form.querySelector('[name="' + name + '"]');
        field.classList.add('is-invalid');
        let feedback = field.parentElement.querySelector('.js-feedback');
        if (!feedback && field.parentElement.classList.contains('input-group')) {
            feedback = field.parentElement.parentElement.querySelector('.js-feedback');
        }
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block js-feedback';
            const anchor = field.parentElement.classList.contains('input-group') ? field.parentElement : field;
            anchor.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = message;
        valid = false;
    }

    function clear(name) {
        const field = form.querySelector('[name="' + name + '"]');
        field.classList.remove('is-invalid');
        const scope = field.closest('.mb-6');
        scope.querySelectorAll('.js-feedback').forEach(function (node) { node.remove(); });
    }

    const name = form.name.value.trim();
    const description = form.description.value.trim();
    clear('name');
    clear('description');
    if (name.length < 3) show('name', 'Le nom doit contenir au moins 3 caractères.');
    else if (name.length > 50) show('name', 'Le nom ne peut pas dépasser 50 caractères.');
    if (description.length < 10) show('description', 'La description doit contenir au moins 10 caractères.');
    else if (description.length > 200) show('description', 'La description ne peut pas dépasser 200 caractères.');
    if (!valid) event.preventDefault();
});

document.querySelectorAll('[data-counter]').forEach(function (hint) {
    const field = document.getElementById(hint.dataset.counter);
    const base = hint.textContent;
    const refresh = function () {
        hint.textContent = base.replace(/\.$/, '') + ' — ' + field.value.trim().length + ' saisis.';
    };
    field.addEventListener('input', refresh);
    refresh();
});
</script>
@endpush
