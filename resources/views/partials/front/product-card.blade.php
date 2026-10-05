<div class="border border-primary rounded position-relative vesitable-item h-100">
    <div class="vesitable-img bg-light">
        @if ($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid w-100 rounded-top" alt="{{ $product->name }}" style="height: 220px; object-fit: contain;">
        @else
            <div class="d-flex align-items-center justify-content-center rounded-top" style="height: 220px;">
                <i class="fas fa-leaf fa-3x text-primary"></i>
            </div>
        @endif
    </div>
    <div class="text-white bg-primary px-3 py-1 rounded position-absolute" style="top: 10px; right: 10px;">{{ $product->category->name }}</div>
    <div class="p-4 rounded-bottom">
        <h4>{{ $product->name }}</h4>
        <p>{{ \Illuminate\Support\Str::limit($product->description, 80) }}</p>
        <p class="mb-3"><i class="fas fa-map-marker-alt text-primary me-2"></i>{{ $product->origin ?: 'Origine non renseignée' }}</p>
        <div class="d-flex justify-content-between align-items-center flex-lg-wrap gap-2">
            <p class="text-dark fs-5 fw-bold mb-0">{{ $product->formattedPrice() }} DT</p>
            <a href="{{ route('produit.detail', $product) }}" class="btn border border-secondary rounded-pill px-3 text-primary"><i class="fa fa-shopping-bag me-2 text-primary"></i> Voir le produit</a>
        </div>
    </div>
</div>
