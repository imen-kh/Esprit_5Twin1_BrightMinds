@extends('layouts.back')

@section('title', 'Catégories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Catégories</h1>
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Ajouter une catégorie</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Nom</th><th>Description</th><th>Statut</th><th>Produits</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse ($categories as $category)
        <tr>
            <td>{{ $category->name }}</td>
            <td>{{ $category->description }}</td>
            <td>
                @if ($category->status === \App\Models\Category::PROPOSED)
                    <span class="badge bg-warning">En attente</span>
                @else
                    <span class="badge bg-success">Validée</span>
                @endif
            </td>
            <td>{{ $category->products_count }}</td>
            <td class="d-flex gap-2">
                @if ($category->status === \App\Models\Category::PROPOSED)
                    <form method="POST" action="{{ route('admin.categories.valider', $category) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-success">Valider</button>
                    </form>
                    <form method="POST" action="{{ route('admin.categories.refuser', $category) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Refuser</button>
                    </form>
                @endif
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.edit', $category) }}">Modifier</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Aucune catégorie enregistrée.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $categories->links() }}</div>
@endsection
