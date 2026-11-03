@extends('layouts.front')

@php
    // Le transformateur gère des produits transformés (tableau), le producteur des matières premières (cartes)
    $transformateur = auth()->user()->isTransformateur();
    $titre = $transformateur ? 'Mes produits' : 'Ajouter un produit';

    // Libellé et couleur Bootstrap des statuts de commande
    $statutsCommande = [
        'en_attente' => ['En attente', 'warning text-dark'],
        'confirmee' => ['Confirmée', 'info'],
        'preparee' => ['Préparée', 'primary'],
        'expediee' => ['Expédiée', 'secondary'],
        'en_transit' => ['En transit', 'secondary'],
        'livree' => ['Livrée', 'success'],
        'refusee' => ['Refusée', 'danger'],
        'annulee' => ['Annulée', 'dark'],
    ];
@endphp

@section('title', $titre)

@section('content')
@include('partials.front.page-header', ['titre' => $titre])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    @if ($transformateur)
        {{-- ===== Tableau de bord du transformateur ===== --}}
        <div class="row g-4 mb-5">
            @foreach ([
                ['Produits transformés', $chiffres['produits'], 'fa-box-open'],
                ['Lots de production', $chiffres['lots'], 'fa-boxes'],
                ['Matières premières reçues', $chiffres['matieres_recues'], 'fa-seedling'],
                ['Commandes en attente', $chiffres['commandes_en_attente'], 'fa-hourglass-half'],
            ] as [$libelle, $valeur, $icone])
                <div class="col-6 col-lg-3">
                    <div class="bg-light rounded p-4 h-100 text-center">
                        <i class="fas {{ $icone }} fa-2x text-secondary mb-3"></i>
                        <h3 class="text-primary mb-1">{{ $valeur }}</h3>
                        <p class="mb-0">{{ $libelle }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Transformateur : gestion des produits transformés ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1">Mes produits transformés</h2>
                <p class="text-muted mb-0">Gérez vos produits transformés.</p>
            </div>
            <a class="btn btn-primary rounded-pill px-4" href="{{ route('acteur.produits.create') }}">Ajouter un produit transformé</a>
        </div>
        <div class="table-responsive border rounded">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th></th><th>Produit transformé</th><th>Catégorie</th><th>Type</th><th>Prix</th><th>Statut</th><th>Lots</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td style="width: 70px;">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?: '—' }}</td>
                        <td><span class="badge bg-success">Transformé</span></td>
                        <td>{{ $product->formattedPrice() }} DT</td>
                        <td>
                            <span class="badge {{ $product->classeStatut() }}">{{ $product->libelleStatut() }}</span>
                            @if ($product->review_note)
                                <small class="d-block text-muted mt-1">{{ $product->review_note }}</small>
                            @endif
                        </td>
                        <td>
                            {{-- module Traçabilité : lots de ce produit --}}
                            <a href="{{ route('gestion.lots.index', ['produit_id' => $product->id]) }}" class="badge bg-secondary text-decoration-none" title="Voir les lots de ce produit">
                                {{ $product->lots_count }} lot{{ $product->lots_count > 1 ? 's' : '' }}
                            </a>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @if ($product->status === \App\Models\Product::PUBLISHED)
                                    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3" href="{{ route('produit.detail', $product) }}">Voir</a>
                                @endif
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="{{ route('acteur.produits.edit', $product) }}">Modifier</a>
                                <button
                                    class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalSuppressionProduit"
                                    data-action="{{ route('acteur.produits.destroy', $product) }}"
                                    data-nom="{{ $product->name }}">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4">Aucun produit transformé enregistré.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- ===== Producteur : matières premières ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
            <p class="mb-0">Un produit ajouté reste en attente jusqu’à la validation d’un administrateur.</p>
            <a class="btn btn-primary rounded-pill px-4 py-3" href="{{ route('acteur.produits.create') }}">Ajouter un produit</a>
        </div>

        <div class="row g-4">
            @forelse ($products as $product)
                <div class="col-md-6 col-xl-4">
                    <div class="rounded bg-light h-100 overflow-hidden d-flex flex-column">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-100" style="height: 180px; object-fit: cover;">
                        <div class="p-4 d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-primary">{{ $product->category?->name ?: '—' }}</span>
                            <span class="fs-5 fw-bold text-primary">{{ $product->formattedPrice() }} DT</span>
                        </div>
                        <h3 class="h4">{{ $product->name }}</h3>
                        <p class="mb-2"><span class="badge {{ $product->classeStatut() }}">{{ $product->libelleStatut() }}</span></p>
                        @if ($product->review_note)
                            <p class="mb-2 text-danger">{{ $product->review_note }}</p>
                        @endif
                        <p class="mb-2">{{ \Illuminate\Support\Str::limit($product->description, 140) }}</p>
                        <p class="mb-4"><i class="fas fa-map-marker-alt text-primary me-2"></i>{{ $product->origin }}</p>
                        <div class="d-flex flex-wrap gap-2 mt-auto">
                            <a class="btn border border-secondary rounded-pill px-4 text-primary" href="{{ route('acteur.produits.edit', $product) }}">Modifier</a>
                            <form method="POST" action="{{ route('acteur.produits.destroy', $product) }}" onsubmit="return confirm('Supprimer ce produit ?');">
                                @csrf @method('DELETE')
                                <button class="btn border border-secondary rounded-pill px-4 text-danger" type="submit">Supprimer</button>
                            </form>
                        </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="bg-light rounded text-center p-5">
                        <h3 class="h4 text-primary">Aucun produit pour le moment</h3>
                        <p class="mb-4">Ajoutez votre premier produit. Il sera publié après validation.</p>
                        <a class="btn btn-primary rounded-pill px-4 py-3" href="{{ route('acteur.produits.create') }}">Ajouter un produit</a>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>

    @if ($transformateur)
        {{-- ===== Mes matières premières : produits bruts commandés, reliés au module Traçabilité ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div>
                <h2 class="h4 mb-1">Mes matières premières</h2>
                <p class="text-muted mb-0">Les produits bruts achetés aux producteurs.</p>
            </div>
            <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('commandes.index') }}">Voir mes commandes</a>
        </div>
        <div class="table-responsive border rounded">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th></th><th>Produit brut</th><th>Producteur</th><th>Quantité</th><th>Commande</th><th>Lot reçu</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($matieres as $ligne)
                    <tr>
                        <td style="width: 70px;">
                            <img src="{{ $ligne->produit->imageUrl() }}" alt="{{ $ligne->produit->name }}" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                        </td>
                        <td>
                            {{ $ligne->produit->name }}
                            <small class="d-block text-muted">{{ $ligne->produit->category?->name }}</small>
                        </td>
                        <td>{{ $ligne->produit->user?->nom_entreprise ?: $ligne->produit->user?->name ?: '—' }}</td>
                        <td>{{ $ligne->quantite }} {{ $ligne->lot?->unite ?? ($ligne->quantite > 1 ? 'unités' : 'unité') }}</td>
                        <td>
                            <span class="badge bg-{{ $statutsCommande[$ligne->commande->statut][1] ?? 'secondary' }}">{{ $statutsCommande[$ligne->commande->statut][0] ?? ucfirst($ligne->commande->statut) }}</span>
                            <small class="d-block text-muted">#{{ $ligne->commande->id }} · {{ ($ligne->commande->date_commande ?? $ligne->commande->created_at)->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            @if ($ligne->lot)
                                <span class="fw-bold text-primary">{{ $ligne->lot->code }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @if ($ligne->lot)
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="{{ route('tracabilite.show', $ligne->lot) }}">Voir la traçabilité</a>
                                    <a class="btn btn-sm btn-primary rounded-pill px-3" href="{{ route('gestion.lots.create', ['lot_origine_id' => $ligne->lot->id]) }}">Transformer ce lot</a>
                                @else
                                    {{-- Bouton désactivé : l'infobulle est portée par le span, un bouton disabled n'en affiche pas --}}
                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Aucun lot disponible pour ce produit pour le moment.">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3" type="button" disabled>Transformer ce lot</button>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <p class="mb-3">Vous n'avez encore commandé aucune matière première.</p>
                            <a class="btn btn-primary rounded-pill px-4" href="{{ route('produits') }}">Parcourir le catalogue</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== Dernières étapes de transformation ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div>
                <h2 class="h4 mb-1">Dernières transformations</h2>
                <p class="text-muted mb-0">Vos trois dernières étapes de transformation déclarées.</p>
            </div>
            <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('gestion.lots.index') }}">Voir mes lots</a>
        </div>
        <div class="row g-4">
            @forelse ($dernieresTransformations as $etape)
                <div class="col-md-4">
                    <div class="bg-light rounded p-4 h-100">
                        <p class="text-muted small mb-2"><i class="fas fa-calendar-alt me-2"></i>{{ $etape->date_etape->format('d/m/Y H:i') }}</p>
                        <h3 class="h6 mb-2">{{ $etape->titre }}</h3>
                        <p class="mb-2">{{ $etape->lot->produit->name }} · <a href="{{ route('tracabilite.show', $etape->lot) }}">{{ $etape->lot->code }}</a></p>
                        <p class="mb-0 small"><i class="fas fa-map-marker-alt text-primary me-2"></i>{{ $etape->lieu }}@if ($etape->temperature_formatee) · {{ $etape->temperature_formatee }}@endif</p>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="bg-light rounded text-center p-4">Aucune étape de transformation pour le moment.</div></div>
            @endforelse
        </div>

        {{-- ===== Confirmation de suppression d'un produit ===== --}}
        <div class="modal fade" id="modalSuppressionProduit" tabindex="-1" aria-labelledby="modalSuppressionProduitTitre" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="">
                    @csrf @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSuppressionProduitTitre">Supprimer le produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Voulez-vous vraiment supprimer <strong data-role="nom"></strong> ?</p>
                        <p class="mb-0 text-danger small">Ses lots et leurs étapes de traçabilité seront supprimés avec lui.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn border border-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
            <div>
                <h2 class="h4 mb-1">Produits commandés</h2>
                <p class="text-muted mb-0">Les produits présents dans vos commandes.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('commandes.index') }}">Voir mes commandes</a>
        </div>
        <div class="table-responsive border rounded">
            <table class="table mb-0">
                <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix actuel</th></tr></thead>
                <tbody>
                @forelse ($orderedProducts as $product)
                    <tr><td>{{ $product->name }}</td><td>{{ $product->category?->name ?: '—' }}</td><td>{{ $product->formattedPrice() }} DT</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center">Aucun produit commandé.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@if ($transformateur)
@push('scripts')
<script>
// Modal de suppression : l'adresse et le nom du produit viennent du bouton cliqué
document.getElementById('modalSuppressionProduit').addEventListener('show.bs.modal', function (event) {
    this.querySelector('form').action = event.relatedTarget.dataset.action;
    this.querySelector('[data-role="nom"]').textContent = event.relatedTarget.dataset.nom;
});

// Infobulles des boutons « Transformer ce lot » désactivés
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
    new bootstrap.Tooltip(element);
});
</script>
@endpush
@endif
