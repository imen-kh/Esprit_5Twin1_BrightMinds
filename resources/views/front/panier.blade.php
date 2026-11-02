@extends('layouts.front')

@section('content')
<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Panier</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="#">Pages</a></li>
        <li class="breadcrumb-item active text-white">Panier</li>
    </ol>
</div>
<!-- Single Page Header End -->

<!-- Cart Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <div class="fw-semibold mb-1">Impossible de comparer la sélection :</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @php
            $catalogueSelection = $catalogue
                ->unique(fn ($product) => mb_strtolower(trim($product->name)).'|'.$product->category_id)
                ->values();
        @endphp

        <div class="card border-0 shadow-sm rounded-4 mb-5">
            <div class="card-body p-4 p-lg-5">
                <span class="badge bg-primary-subtle text-primary mb-2">AI Smart Basket</span>
                <h2 class="h3 mb-2">Composer et comparer mon panier</h2>
                <p class="text-muted">Choisissez les produits et les quantités. NutriTrace compare les offres réelles, les producteurs, les stocks et les certifications disponibles.</p>
                @if(!empty($parseMessage))
                    <div class="alert alert-info">{{ $parseMessage }}</div>
                @endif
                <div class="border rounded-3 p-3 mb-4 bg-white">
                    <h3 class="h6 mb-2">Étape 1 — Assistant facultatif</h3>
                    <p class="small text-muted mb-2">Décrivez votre besoin en une phrase. Si Gemini est désactivé, la sélection manuelle reste entièrement fonctionnelle.</p>
                    <form action="{{ route('panier.smart.parse') }}" method="POST">
                        @csrf
                        <label for="need-description" class="form-label fw-semibold">Décrire votre besoin en une phrase</label>
                        <textarea name="need_description" id="need-description" class="form-control mb-2" rows="2" maxlength="2000" placeholder="Exemple : J'ai 50 DT, je veux 2 kg de tomates et 3 kg de pommes, au prix le plus bas.">{{ old('need_description', $naturalText) }}</textarea>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <input type="number" name="budget" class="form-control" style="max-width: 160px" min="0.001" step="0.001" value="{{ old('budget', $smartBudget) }}" placeholder="Budget DT">
                            <button type="submit" class="btn btn-outline-primary rounded-pill">Comprendre ma demande</button>
                            <span class="small text-muted">Un seul appel Gemini, puis vérification avec le catalogue réel.</span>
                        </div>
                        @error('need_description')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </form>
                </div>
                <h3 class="h5 mb-3">Étape 2 — Composer ma demande</h3>
                <form action="{{ route('panier.smart.analyze') }}" method="POST" id="smart-basket-form">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="smart-budget" class="form-label">Budget maximum</label>
                            <div class="input-group">
                                <input type="number" name="budget" id="smart-budget" class="form-control" min="0.001" step="0.001" value="{{ old('budget', $smartBudget) }}" placeholder="50" required>
                                <span class="input-group-text">DT</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="preference" class="form-label">Préférence</label>
                            <select name="preference" id="preference" class="form-select">
                                <option value="cheapest" @selected(old('preference', $smartInput['preference'] ?? 'cheapest') === 'cheapest')>Moins cher</option>
                                <option value="local" @selected(old('preference', $smartInput['preference'] ?? '') === 'local')>Origine renseignée</option>
                                <option value="certified" @selected(old('preference', $smartInput['preference'] ?? '') === 'certified')>Certifié</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="selection-mode" class="form-label">Producteurs</label>
                            <select name="selection_mode" id="selection-mode" class="form-select">
                                <option value="auto" @selected(old('selection_mode', $smartInput['selection_mode'] ?? 'auto') === 'auto')>Sélection automatique</option>
                                <option value="manual" @selected(old('selection_mode', $smartInput['selection_mode'] ?? '') === 'manual')>Je choisirai</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary rounded-pill w-100">Comparer et proposer</button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h3 class="h5 mb-0">Produits souhaités</h3>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="add-smart-product">+ Ajouter un produit</button>
                    </div>
                    <input type="search" id="smart-product-search" class="form-control form-control-sm mb-3" placeholder="Rechercher un produit dans le catalogue…" aria-label="Rechercher un produit">
                    <select id="smart-category-filter" class="form-select form-select-sm mb-3" aria-label="Filtrer par catégorie">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                    <div class="small text-muted mb-3"><i class="fa fa-info-circle me-1"></i>Les produits avec la mention « stock épuisé » sont visibles pour conserver le catalogue complet, mais ne peuvent pas être sélectionnés.</div>
                    @error('requests')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    <div id="smart-product-lines">
                        @php $oldRequests = old('requests', $smartInput['requests'] ?? []); @endphp
                        @forelse($oldRequests as $lineIndex => $line)
                            <div class="smart-product-line border rounded-3 bg-light p-3 mb-3">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5"><label class="form-label small fw-semibold">Produit souhaité</label><select name="requests[{{ $lineIndex }}][product_id]" class="form-select smart-product-select" required>@foreach($catalogueSelection as $product)<option value="{{ $product->id }}" data-category="{{ $product->category_id }}" @selected((int) ($line['product_id'] ?? 0) === $product->id) @disabled((int) ($product->stock_disponible ?? 0) < 1)>{{ $product->name }} — à partir de {{ $product->formattedPrice() }} DT — {{ (int) ($product->stock_disponible ?? 0) > 0 ? 'stock disponible' : 'stock épuisé' }}</option>@endforeach</select></div>
                                    <div class="col-lg-4"><label class="form-label small fw-semibold">Producteur</label><select name="choices[{{ $lineIndex }}]" class="form-select smart-producer-select"><option value="">Automatique — meilleur prix</option>@foreach(($producerOptions[$line['product_id'] ?? 0] ?? []) as $offer)<option value="{{ $offer['id'] }}">{{ $offer['producer'] }} — {{ $offer['price'] }} DT — {{ $offer['stock'] > 0 ? 'stock '.$offer['stock'] : 'stock épuisé' }}</option>@endforeach</select></div>
                                    <div class="col-lg-2"><label class="form-label small fw-semibold">Quantité</label><input type="number" name="requests[{{ $lineIndex }}][quantite]" value="{{ $line['quantite'] ?? 1 }}" min="1" class="form-control" required aria-label="Quantité"></div>
                                    <div class="col-lg-1"><button type="button" class="btn btn-outline-danger w-100 remove-smart-product" aria-label="Supprimer">×</button></div>
                                </div>
                            </div>
                        @empty
                            <div class="smart-product-line border rounded-3 bg-light p-3 mb-3">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5"><label class="form-label small fw-semibold">Produit souhaité</label><select name="requests[0][product_id]" class="form-select smart-product-select" required><option value="">Sélectionner un produit</option>@foreach($catalogueSelection as $product)<option value="{{ $product->id }}" data-category="{{ $product->category_id }}" @disabled((int) ($product->stock_disponible ?? 0) < 1)>{{ $product->name }} — à partir de {{ $product->formattedPrice() }} DT — {{ (int) ($product->stock_disponible ?? 0) > 0 ? 'stock disponible' : 'stock épuisé' }}</option>@endforeach</select></div>
                                    <div class="col-lg-4"><label class="form-label small fw-semibold">Producteur</label><select name="choices[0]" class="form-select smart-producer-select"><option value="">Automatique — meilleur prix</option></select></div>
                                    <div class="col-lg-2"><label class="form-label small fw-semibold">Quantité</label><input type="number" name="requests[0][quantite]" value="1" min="1" class="form-control" required aria-label="Quantité"></div>
                                    <div class="col-lg-1"><button type="button" class="btn btn-outline-danger w-100 remove-smart-product" aria-label="Supprimer">×</button></div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </form>
            </div>
        </div>

        @if(count($proposals) > 0)
            <div class="mb-5">
                <h2 class="h3 mb-2">Étape 3 — Comparer et choisir une proposition</h2>
                <p class="text-muted">Les montants sont calculés à partir des prix et stocks présents en base au moment de l’analyse.</p>
                @if(!empty($aiSummary))
                    <div class="alert alert-info border-0 shadow-sm"><strong>Assistant Gemini :</strong> {{ $aiSummary }}</div>
                @endif
                <div class="row g-4 justify-content-center">
                    @foreach($proposals as $proposalIndex => $proposal)
                        @php $proposalItems = $proposal['items']; @endphp
                        <div class="col-xl-5 col-lg-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body p-4">
                                    <h3 class="h5 mb-1">{{ $proposal['label'] }}</h3>
                                    <p class="small text-muted">{{ $proposal['description'] }}</p>
                                    @if(!$proposal['complete'])<div class="alert alert-warning small py-2">Quantités réduites pour respecter le budget.</div>@endif
                                    <form action="{{ route('panier.smart.confirm') }}" method="POST" class="smart-proposal-form" data-budget="{{ $proposal['budget'] }}">
                                        @csrf
                                        <input type="hidden" name="budget" value="{{ number_format($proposal['budget'] / 1000, 3, '.', '') }}">
                                        @foreach($proposalItems as $itemIndex => $item)
                                            @php
                                                $proposalImage = $item['offer']['image']
                                                    ?: (str_contains(mb_strtolower($item['name']), 'confiture') || str_contains(mb_strtolower($item['name']), 'harissa')
                                                        ? asset('front/img/best-product-2.jpg')
                                                        : (str_contains(mb_strtolower($item['name']), 'orange') || str_contains(mb_strtolower($item['name']), 'fraise')
                                                            ? asset('front/img/fruite-item-2.jpg')
                                                            : asset('front/img/vegetable-item-3.png')));
                                            @endphp
                                            <div class="border-bottom pb-3 mb-3 smart-proposal-item" data-price="{{ $item['offer']['price_millimes'] }}">
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <img src="{{ $proposalImage }}" class="rounded-3 border" style="width: 64px; height: 64px; object-fit: cover;" alt="{{ $item['name'] }}">
                                                    <div class="fw-semibold">{{ $item['name'] }}</div>
                                                </div>
                                                <div class="small text-muted mb-2">Demandé : {{ $item['requested_quantity'] }}</div>
                                                <div class="table-responsive mb-2">
                                                    <table class="table table-sm small mb-0">
                                                        <thead><tr><th>Producteur</th><th>Prix</th><th>Stock</th><th>Certification</th></tr></thead>
                                                        <tbody>@foreach($item['offers'] as $offer)<tr class="{{ $offer['product_id'] === $item['offer']['product_id'] ? 'table-primary' : '' }}"><td>{{ $offer['producer'] }}</td><td>{{ \App\Models\Product::formatAmount($offer['price']) }} DT</td><td>{{ $offer['stock'] }}</td><td>{{ collect($offer['certifications'])->pluck('name')->join(', ') ?: '—' }}</td></tr>@endforeach</tbody>
                                                    </table>
                                                </div>
                                                <select name="items[{{ $itemIndex }}][product_id]" class="form-select form-select-sm mb-2 smart-offer-select" required>
                                                    @foreach($item['offers'] as $offer)
                                                        <option value="{{ $offer['product_id'] }}" data-price="{{ $offer['price_millimes'] }}" data-stock="{{ $offer['stock'] }}" @selected($offer['product_id'] === $item['offer']['product_id'])>{{ $offer['producer'] }} — {{ \App\Models\Product::formatAmount($offer['price']) }} DT — stock {{ $offer['stock'] }}@if($offer['certified']) — certifié@endif</option>
                                                    @endforeach
                                                </select>
                                                @if(count($item['offer']['certifications']) > 0)<div class="small text-success mb-2">✓ {{ collect($item['offer']['certifications'])->pluck('name')->join(', ') }}</div>@endif
                                                <div class="input-group input-group-sm"><span class="input-group-text">Quantité</span><input type="number" name="items[{{ $itemIndex }}][quantite]" value="{{ $item['quantite'] }}" min="1" max="{{ $item['offer']['stock'] }}" class="form-control smart-quantity" required></div>
                                            </div>
                                        @endforeach
                                        <div class="d-flex justify-content-between mb-3"><span>Total</span><strong class="smart-total">{{ \App\Models\Product::formatAmount($proposal['total'] / 1000) }} DT</strong><span class="text-success smart-remaining">Reste {{ \App\Models\Product::formatAmount(($proposal['budget'] - $proposal['total']) / 1000) }} DT</span></div>
                                        <button type="submit" class="btn btn-primary rounded-pill w-100">Choisir cette proposition</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($smartBudget !== null)
            <div class="alert alert-warning mb-5">Aucune proposition complète n’est possible avec les produits, stocks et producteurs disponibles.</div>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead>
                  <tr>
                    <th scope="col">Produits</th>
                    <th scope="col">Nom</th>
                    <th scope="col">Prix</th>
                    <th scope="col">Quantité</th>
                    <th scope="col">Total</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                    @php $sous_total = 0; @endphp
                    @if($panier && $panier->products)
                        @foreach($panier->products as $product)
                            @php 
                                $total_produit = $product->price * $product->pivot->quantite;
                                $sous_total += $total_produit;
                                $cartImage = $product->imageUrl()
                                    ?: (str_contains(mb_strtolower($product->name), 'confiture') || str_contains(mb_strtolower($product->name), 'harissa')
                                        ? asset('front/img/best-product-2.jpg')
                                        : (str_contains(mb_strtolower($product->name), 'orange') || str_contains(mb_strtolower($product->name), 'fraise')
                                            ? asset('front/img/fruite-item-2.jpg')
                                            : asset('front/img/vegetable-item-3.png')));
                            @endphp
                            <tr>
                                <th scope="row">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $cartImage }}" class="img-fluid me-5 rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="{{ $product->name }}">
                                    </div>
                                </th>
                                <td>
                                    <p class="mb-0 mt-4">{{ $product->name }}</p>
                                </td>
                                <td>
                                    <p class="mb-0 mt-4">{{ $product->formattedPrice() }} DT</p>
                                </td>
                                <td>
                                    <form action="{{ route('panier.update', $product->id) }}" method="POST" class="d-flex align-items-center mt-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="input-group quantity" style="width: 100px;">
                                            <div class="input-group-btn">
                                                <button type="submit" name="quantite" value="{{ max(1, $product->pivot->quantite - 1) }}" class="btn btn-sm btn-minus rounded-circle bg-light border">
                                                    <i class="fa fa-minus"></i>
                                                </button>
                                            </div>
                                            <input type="text" class="form-control form-control-sm text-center border-0" value="{{ $product->pivot->quantite }}" readonly>
                                            <div class="input-group-btn">
                                                <button type="submit" name="quantite" value="{{ $product->pivot->quantite + 1 }}" class="btn btn-sm btn-plus rounded-circle bg-light border">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <p class="mb-0 mt-4">{{ \App\Models\Product::formatAmount($total_produit) }} DT</p>
                                </td>
                                <td>
                                    <form action="{{ route('panier.remove', $product->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-md rounded-circle bg-light border mt-4">
                                            <i class="fa fa-times text-danger"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row g-4 justify-content-end">
            <div class="col-8"></div>
            <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                <div class="bg-light rounded">
                    <div class="p-4">
                        <h1 class="display-6 mb-4">Panier <span class="fw-normal">Total</span></h1>
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="mb-0 me-4">Sous-total :</h5>
                            <p class="mb-0">{{ \App\Models\Product::formatAmount($sous_total) }} DT</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0 me-4">Livraison</h5>
                            <div class="">
                                <p class="mb-0">Forfait : 3,000 DT</p>
                            </div>
                        </div>
                        <p class="mb-0 text-end">Livraison en Tunisie.</p>
                    </div>
                    <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                        <h5 class="mb-0 ps-4 me-4">Total</h5>
                        <p class="mb-0 pe-4">{{ \App\Models\Product::formatAmount($sous_total + ($sous_total > 0 ? 3 : 0)) }} DT</p>
                    </div>
                    @if($sous_total > 0)
                        <a href="{{ route('commande') }}" class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4">Passer la commande</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Cart Page End -->
@push('scripts')
@php
    $catalogueOptions = $catalogueSelection->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'categoryId' => $product->category_id,
            'producer' => $product->user?->nom_entreprise ?: ($product->user?->name ?? 'Producteur non renseigné'),
            'price' => $product->formattedPrice(),
            'stock' => (int) ($product->stock_disponible ?? 0),
        ];
    })->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lines = document.getElementById('smart-product-lines');
    const addButton = document.getElementById('add-smart-product');
    const search = document.getElementById('smart-product-search');
    const categoryFilter = document.getElementById('smart-category-filter');
    const catalogue = @json($catalogueOptions);
    const producerOptions = @json($producerOptions);
    let lineIndex = lines ? lines.querySelectorAll('.smart-product-line').length : 0;

    function optionsHtml() {
        return '<option value="">Sélectionner un produit</option>' + catalogue.map(product => `<option value="${product.id}" data-category="${product.categoryId}" ${product.stock < 1 ? 'disabled' : ''}>${product.name} — ${product.producer} — ${product.price} DT — ${product.stock > 0 ? 'stock ' + product.stock : 'stock épuisé'}</option>`).join('');
    }

    function refreshProducers(row) {
        const productSelect = row.querySelector('.smart-product-select');
        const producerSelect = row.querySelector('.smart-producer-select');
        if (!productSelect || !producerSelect) return;
        const selected = producerSelect.value;
        const offers = producerOptions[productSelect.value] || [];
        producerSelect.innerHTML = '<option value="">Automatique — meilleur prix</option>' + offers.map(offer => `<option value="${offer.id}">${offer.producer} — ${offer.price} DT — ${offer.stock > 0 ? 'stock ' + offer.stock : 'stock épuisé'}</option>`).join('');
        if (offers.some(offer => String(offer.id) === selected)) producerSelect.value = selected;
    }

    function refreshFilters() {
        const term = (search?.value || '').toLocaleLowerCase();
        const category = categoryFilter?.value || '';
        document.querySelectorAll('.smart-product-select option').forEach(option => {
            const textMatch = option.value === '' || option.textContent.toLocaleLowerCase().includes(term);
            const categoryMatch = !category || option.dataset.category === category;
            option.hidden = option.value !== '' && (!textMatch || !categoryMatch);
        });
    }

    function bindRemove(button) {
        button.addEventListener('click', () => button.closest('.smart-product-line')?.remove());
    }

    document.querySelectorAll('.remove-smart-product').forEach(bindRemove);
    addButton?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 smart-product-line';
        row.innerHTML = `<div class="row g-3 align-items-end"><div class="col-lg-5"><label class="form-label small fw-semibold">Produit souhaité</label><select name="requests[${lineIndex}][product_id]" class="form-select smart-product-select" required>${optionsHtml()}</select></div><div class="col-lg-4"><label class="form-label small fw-semibold">Producteur</label><select name="choices[${lineIndex}]" class="form-select smart-producer-select"><option value="">Automatique — meilleur prix</option></select></div><div class="col-lg-2"><label class="form-label small fw-semibold">Quantité</label><input type="number" name="requests[${lineIndex}][quantite]" value="1" min="1" class="form-control" required aria-label="Quantité"></div><div class="col-lg-1"><button type="button" class="btn btn-outline-danger w-100 remove-smart-product" aria-label="Supprimer">×</button></div></div>`;
        lines.appendChild(row);
        bindRemove(row.querySelector('.remove-smart-product'));
        row.querySelector('.smart-product-select').addEventListener('change', () => refreshProducers(row));
        lineIndex++;
    });

    search?.addEventListener('input', refreshFilters);
    categoryFilter?.addEventListener('change', refreshFilters);
    document.querySelectorAll('.smart-product-line').forEach(row => {
        row.querySelector('.smart-product-select')?.addEventListener('change', () => refreshProducers(row));
        refreshProducers(row);
    });

    function refreshProposal(form) {
        let total = 0;
        form.querySelectorAll('.smart-proposal-item').forEach(item => {
            const select = item.querySelector('.smart-offer-select');
            const quantity = item.querySelector('.smart-quantity');
            const option = select.options[select.selectedIndex];
            const price = Number(option.dataset.price || 0);
            const stock = Number(option.dataset.stock || 0);
            quantity.max = stock;
            if (Number(quantity.value) > stock) quantity.value = stock;
            total += price * Number(quantity.value || 0);
        });
        const budget = Number(form.dataset.budget || 0);
        form.querySelector('.smart-total').textContent = (total / 1000).toLocaleString('fr-FR', {minimumFractionDigits: 3}) + ' DT';
        form.querySelector('.smart-remaining').textContent = 'Reste ' + ((budget - total) / 1000).toLocaleString('fr-FR', {minimumFractionDigits: 3}) + ' DT';
    }

    document.querySelectorAll('.smart-proposal-form').forEach(form => {
        form.querySelectorAll('.smart-offer-select, .smart-quantity').forEach(input => input.addEventListener('change', () => refreshProposal(form)));
        refreshProposal(form);
    });
});
</script>
@endpush
@endsection
