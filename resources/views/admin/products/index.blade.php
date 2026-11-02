@extends('layouts.back')

@section('title', 'Produits')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Produits du catalogue</h4>
        <p class="mb-0 text-body-secondary">Un produit soumis par un acteur n’est publié qu’après validation.</p>
    </div>
    @can('valider-produits')
        <a class="btn btn-primary" href="{{ route('admin.produits.validation') }}">File de validation</a>
    @endcan
</div>
<div class="card"><div class="table-responsive"><table class="table">
    <thead><tr><th>Produit</th><th>Catégorie</th><th>Acteur</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($products as $product)
        <tr>
            <td>{{ $product->name }}</td>
            <td>{{ $product->category?->name ?: '—' }}</td>
            <td>{{ $product->user?->name ?: '—' }}</td>
            <td><span class="badge {{ $product->classeStatut() }}">{{ $product->libelleStatut() }}</span></td>
            <td class="d-flex flex-wrap gap-2">
                @can('valider-produits')
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.produits.revue', $product) }}">Examiner</a>
                    @if ($product->status === \App\Models\Product::PUBLISHED)
                        <form method="POST" action="{{ route('admin.produits.statut', $product) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning">Dépublier</button>
                        </form>
                    @endif
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Aucun produit enregistré.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
