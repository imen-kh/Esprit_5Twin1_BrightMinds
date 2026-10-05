@extends('layouts.back')

@section('title', 'Catégories')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Catégories</h4>
        <p class="mb-0 text-body-secondary">Celles créées par l’administrateur, et celles proposées par les producteurs à accepter.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">
        <i class="bx bx-plus me-1"></i> Ajouter une catégorie
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar-initial rounded bg-label-primary p-2"><i class="bx bx-category bx-sm"></i></span>
                <div>
                    <h4 class="mb-0">{{ $counts['total'] }}</h4>
                    <small class="text-body-secondary">Catégories au total</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar-initial rounded bg-label-warning p-2"><i class="bx bx-time bx-sm"></i></span>
                <div>
                    <h4 class="mb-0">{{ $counts['pending'] }}</h4>
                    <small class="text-body-secondary">À accepter (producteurs)</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar-initial rounded bg-label-success p-2"><i class="bx bx-check-circle bx-sm"></i></span>
                <div>
                    <h4 class="mb-0">{{ $counts['validated'] }}</h4>
                    <small class="text-body-secondary">Déjà validées</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Propositions des producteurs</h5>
            <small class="text-body-secondary">Ces catégories n’apparaissent dans le catalogue qu’après votre validation.</small>
        </div>
        <span class="badge bg-label-warning">{{ $pending->count() }} en attente</span>
    </div>
    <div class="card-body">
        @forelse ($pending as $category)
            <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="mb-0">{{ $category->name }}</h6>
                            <span class="badge bg-label-warning">En attente</span>
                            <span class="badge bg-label-info">Producteur</span>
                        </div>
                        <p class="mb-1">{{ $category->description }}</p>
                        <small class="text-body-secondary">
                            Proposée par {{ $category->proposer->name ?? 'un producteur' }}
                            · {{ $category->products_count }} produit{{ $category->products_count > 1 ? 's' : '' }}
                        </small>
                    </div>
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <form method="POST" action="{{ route('admin.categories.valider', $category) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success" type="submit"><i class="bx bx-check me-1"></i> Accepter</button>
                        </form>
                        <form method="POST" action="{{ route('admin.categories.refuser', $category) }}" onsubmit="return confirm('Refuser cette proposition ?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bx bx-x me-1"></i> Refuser</button>
                        </form>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.edit', $category) }}"><i class="bx bx-edit-alt me-1"></i> Modifier</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="mb-0 text-body-secondary">Aucune proposition en attente. Les producteurs peuvent suggérer une catégorie depuis leur fiche produit.</p>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Catégories validées</h5>
        <small class="text-body-secondary">Créées par l’administrateur, ou proposées par un producteur puis acceptées.</small>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Origine</th>
                    <th>Produits</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td class="fw-medium">{{ $category->name }}</td>
                    <td>{{ $category->description }}</td>
                    <td>
                        @if ($category->origin === \App\Models\Category::ORIGIN_PRODUCER)
                            <span class="badge bg-label-info">Producteur</span>
                            @if ($category->proposer)
                                <div class="small text-body-secondary">{{ $category->proposer->name }}</div>
                            @endif
                        @else
                            <span class="badge bg-label-primary">Admin</span>
                        @endif
                    </td>
                    <td>{{ $category->products_count }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.edit', $category) }}">Modifier</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-body-secondary">Aucune catégorie validée pour le moment.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($categories->hasPages())
        <div class="card-footer">{{ $categories->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
