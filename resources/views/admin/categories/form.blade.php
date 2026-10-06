@extends('layouts.back')

@section('title', $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie')

@section('content')
<h1 class="h3 mb-4">{{ $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie' }}</h1>
<form class="card card-body" method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" novalidate>
    @csrf
    @if ($category->exists) @method('PUT') @endif
    <div class="mb-3">
        <label class="form-label" for="name">Nom</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div><button class="btn btn-primary">Enregistrer</button> <a class="btn btn-secondary" href="{{ route('admin.categories.index') }}">Annuler</a></div>
</form>
@endsection
