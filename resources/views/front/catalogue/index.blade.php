@extends('layouts.front')

@section('title', 'Produits')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Catalogue des produits</h1>
</div>
<div class="container py-5">
    <form id="catalogue-search" method="GET" action="{{ route('produits') }}" class="row g-2 mb-4">
        <div class="col-md-7">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit" autocomplete="off">
        </div>
        <div class="col-md-5">
            <select class="form-select" name="category_id">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <div id="catalogue-results">
        <div class="row g-4">
            @forelse ($products as $product)
                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <span class="badge bg-secondary">{{ $product->category->name }}</span>
                        <h2 class="h5 mt-3">{{ $product->name }}</h2>
                        <p>{{ $product->description }}</p>
                        <p><strong>Origine :</strong> {{ $product->origin ?: 'Non renseignée' }}</p>
                        <p class="fw-bold">{{ number_format((float) $product->price, 3, ',', ' ') }} DT</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="btn btn-outline-primary" href="{{ route('produit.detail', $product) }}">Voir le produit</a>
                            <form action="{{ route('panier.add') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="quantite" value="1">
                                <button type="submit" class="btn border border-secondary rounded-pill px-3 text-primary"><i class="fa fa-shopping-bag me-2 text-primary"></i> Ajouter</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p>Aucun produit publié ne correspond à votre recherche.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const catalogueSearch = document.getElementById('catalogue-search');
const catalogueResults = document.getElementById('catalogue-results');
let catalogueTimer;
let catalogueRequest = 0;

catalogueSearch.addEventListener('submit', function (event) {
    event.preventDefault();
    searchCatalogue();
});

catalogueSearch.querySelector('[name="q"]').addEventListener('input', function () {
    clearTimeout(catalogueTimer);
    catalogueTimer = setTimeout(searchCatalogue, 250);
});

catalogueSearch.querySelector('[name="category_id"]').addEventListener('change', searchCatalogue);

async function searchCatalogue() {
    const requestId = ++catalogueRequest;
    const params = new URLSearchParams(new FormData(catalogueSearch));
    if (!params.get('q')) {
        params.delete('q');
    }
    if (!params.get('category_id')) {
        params.delete('category_id');
    }
    const query = params.toString();
    const url = catalogueSearch.action + (query ? '?' + query : '');
    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (requestId !== catalogueRequest) {
        return;
    }
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const next = doc.getElementById('catalogue-results');
    if (next) {
        catalogueResults.innerHTML = next.innerHTML;
    }
    history.replaceState(null, '', url);
}
</script>
@endpush
