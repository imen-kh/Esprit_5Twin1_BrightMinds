@extends('layouts.front')

@section('title', 'Produits')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Catalogue des produits</h1>
</div>
<div class="container-fluid vesitable py-5">
    <div class="container">
    <form id="catalogue-search" method="GET" action="{{ route('produits') }}" class="bg-light rounded p-3 p-md-4 mb-4">
        <div class="row g-3">
            <div class="col-md-7 position-relative">
                <input id="catalogue-query" class="form-control border-secondary rounded-pill py-3 px-4" name="q" value="{{ request('q') }}" placeholder="Ex. légumes de Nabeul à moins de 5 DT" autocomplete="off">
                <div id="catalogue-suggestions" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 5;"></div>
            </div>
            <div class="col-md-5">
                <select class="form-select border-secondary rounded-pill py-3 px-4" name="category_id">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
    <div id="catalogue-results">
        @if ($criteres['resume'] !== '')
            <p class="text-muted mb-3">{{ $criteres['resume'] }}</p>
        @endif
        @if ($products->isNotEmpty())
            <div class="owl-carousel product-carousel">
                @foreach ($products as $product)
                    @include('partials.front.product-card', ['product' => $product])
                @endforeach
            </div>
        @else
            <div class="rounded border border-primary text-center p-5">
                <p class="mb-0">Aucun produit publié ne correspond à votre recherche.</p>
            </div>
        @endif
        <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function initProductCarousel(root) {
    const carousel = (root || document).querySelector('.product-carousel');
    if (!carousel || carousel.classList.contains('owl-loaded') || typeof $ === 'undefined') {
        return;
    }
    const count = carousel.children.length;
    $(carousel).owlCarousel({
        autoplay: count > 1,
        smartSpeed: 1500,
        center: false,
        dots: true,
        loop: count > 4,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0: { items: 1 },
            768: { items: 2 },
            992: { items: 3 },
            1200: { items: 4 }
        }
    });
}
initProductCarousel(document);


const catalogueSearch = document.getElementById('catalogue-search');
const catalogueResults = document.getElementById('catalogue-results');
const catalogueQuery = document.getElementById('catalogue-query');
const catalogueSuggestions = document.getElementById('catalogue-suggestions');
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
        initProductCarousel(catalogueResults);
    }
    history.replaceState(null, '', url);
    showCatalogueSuggestions();
}

async function showCatalogueSuggestions() {
    const term = catalogueQuery.value.trim();
    catalogueSuggestions.replaceChildren();
    if (term === '') {
        catalogueSuggestions.classList.add('d-none');
        return;
    }
    const response = await fetch(@json(route('produits.suggestions')) + '?q=' + encodeURIComponent(term), {
        headers: { 'Accept': 'application/json' }
    });
    const products = await response.json();
    products.forEach(function (product) {
        const link = document.createElement('a');
        link.className = 'list-group-item list-group-item-action';
        link.href = product.url;
        const title = document.createElement('strong');
        title.textContent = product.name;
        const meta = document.createElement('small');
        meta.className = 'd-block text-muted';
        meta.textContent = product.category + ' · ' + product.price;
        link.append(title, meta);
        catalogueSuggestions.append(link);
    });
    catalogueSuggestions.classList.toggle('d-none', products.length === 0);
}

document.addEventListener('click', function (event) {
    if (!catalogueSearch.contains(event.target)) {
        catalogueSuggestions.classList.add('d-none');
    }
});
</script>
@endpush
