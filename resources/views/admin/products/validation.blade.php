@extends('layouts.back')

@section('title', 'Validation des produits')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Produits en attente</h4>
        <p class="mb-0 text-body-secondary">L’analyse IA est un conseil. Elle ne publie jamais un produit à votre place.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.produits.index') }}">Tous les produits</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Produit</th><th>Catégorie</th><th>Acteur</th><th>Soumis le</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $product->imageUrl() }}" alt="" width="48" height="48" class="rounded" style="object-fit: cover;">
                            <div>
                                <div class="fw-medium">{{ $product->name }}</div>
                                <small class="text-body-secondary">{{ $product->formattedPrice() }} DT · {{ $product->origin }}</small>
                                @if (($product->quality_analysis['evaluable'] ?? false) === true)
                                    <small class="d-block text-primary">Qualité visuelle {{ $product->quality_analysis['score'] }}/100 · {{ $product->quality_analysis['classification'] }}</small>
                                @elseif ($product->quality_analysis)
                                    <small class="d-block text-body-secondary">Photo insuffisante pour la qualité visuelle</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $product->category?->name ?: '—' }}</td>
                    <td>{{ $product->user?->name ?: '—' }}</td>
                    <td>{{ $product->created_at?->format('d/m/Y H:i') }}</td>
                    <td><a class="btn btn-sm btn-primary" href="{{ route('admin.produits.revue', $product) }}">Examiner</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">Aucun produit en attente de validation.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
