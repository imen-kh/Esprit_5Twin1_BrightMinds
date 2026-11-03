@extends('layouts.front')

@section('title', 'Commande')

@section('content')
<!-- Single Page Header start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Commande</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
        <li class="breadcrumb-item"><a href="#">Pages</a></li>
        <li class="breadcrumb-item active text-white">Commande</li>
    </ol>
</div>
<!-- Single Page Header End -->


<!-- Checkout Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <h1 class="mb-4">Informations de facturation</h1>
        
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('commande.store') }}" method="POST" id="commande-form" novalidate>
            @csrf
            <div class="row g-5">
                <div class="col-md-12 col-lg-6 col-xl-7">
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3" for="prenom">Prénom<sup>*</sup></label>
                                <input type="text" id="prenom" name="prenom" class="form-control @error('prenom') is-invalid @enderror" value="{{ old('prenom', $user->name ?? '') }}" autocomplete="given-name">
                                @error('prenom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3" for="nom">Nom<sup>*</sup></label>
                                <input type="text" id="nom" name="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom', $user->name ?? '') }}" autocomplete="family-name">
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="adresse">Adresse <sup>*</sup></label>
                        <input type="text" id="adresse" name="adresse" class="form-control @error('adresse') is-invalid @enderror" placeholder="Numéro et nom de la rue" value="{{ old('adresse', $user->adresse ?? '') }}" autocomplete="street-address">
                        @error('adresse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="ville">Ville<sup>*</sup></label>
                        <input type="text" id="ville" name="ville" class="form-control @error('ville') is-invalid @enderror" value="{{ old('ville', $user->ville ?? '') }}" autocomplete="address-level2">
                        @error('ville')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="code_postal">Code postal<sup>*</sup></label>
                        <input type="text" id="code_postal" name="code_postal" class="form-control @error('code_postal') is-invalid @enderror" value="{{ old('code_postal', $user->code_postal ?? '') }}" autocomplete="postal-code">
                        @error('code_postal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="telephone">Téléphone<sup>*</sup></label>
                        <input type="text" id="telephone" name="telephone" class="form-control @error('telephone') is-invalid @enderror" value="{{ old('telephone', $user->telephone ?? '') }}" inputmode="numeric" autocomplete="tel" aria-describedby="telephone-help">
                        <small id="telephone-help" class="form-text text-muted">8 chiffres, par exemple 22123456.</small>
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="email">Adresse Email<sup>*</sup></label>
                        <input type="text" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3" for="commentaire">Commentaire</label>
                        <textarea id="commentaire" name="commentaire" class="form-control @error('commentaire') is-invalid @enderror" rows="4">{{ old('commentaire') }}</textarea>
                        @error('commentaire')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-12 col-lg-6 col-xl-5">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Produit</th>
                                    <th scope="col">Nom</th>
                                    <th scope="col">Prix</th>
                                    <th scope="col">Quantité</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sous_total = 0; @endphp
                                @foreach($panier->products as $product)
                                    @php 
                                        $total_produit = $product->price * $product->pivot->quantite;
                                        $sous_total += $total_produit;
                                    @endphp
                                    <tr>
                                        <th scope="row">
                                            <div class="d-flex align-items-center mt-2">
                                                <img src="{{ $product->imageUrl() }}" class="img-fluid rounded-circle" style="width: 90px; height: 90px; object-fit: cover;" alt="{{ $product->name }}">
                                            </div>
                                        </th>
                                        <td class="py-5">{{ $product->name }}</td>
                                        <td class="py-5">{{ $product->formattedPrice() }} DT</td>
                                        <td class="py-5">{{ $product->pivot->quantite }}</td>
                                        <td class="py-5">{{ \App\Models\Product::formatAmount($total_produit) }} DT</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-5"></td>
                                    <td class="py-5"></td>
                                    <td class="py-5">
                                        <p class="mb-0 text-dark py-3">Sous-total</p>
                                    </td>
                                    <td class="py-5">
                                        <div class="py-3 border-bottom border-top">
                                            <p class="mb-0 text-dark">{{ \App\Models\Product::formatAmount($sous_total) }} DT</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-5">
                                        <p class="mb-0 text-dark py-4">Livraison</p>
                                    </td>
                                    <td colspan="3" class="py-5">
                                        <div class="form-check text-start">
                                            <input type="radio" class="form-check-input bg-primary border-0 @error('shipping') is-invalid @enderror" id="Shipping-2" name="shipping" value="3" {{ old('shipping', '3') === '3' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="Shipping-2">Forfait : 3,000 DT</label>
                                            @error('shipping')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td class="py-5">
                                        <p class="mb-0 text-dark text-uppercase py-3">TOTAL</p>
                                    </td>
                                    <td class="py-5"></td>
                                    <td class="py-5"></td>
                                    <td class="py-5">
                                        <div class="py-3 border-bottom border-top">
                                            <p class="mb-0 text-dark">{{ \App\Models\Product::formatAmount($sous_total + 3) }} DT</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                        <button type="submit" class="btn border-secondary py-3 px-4 text-uppercase w-100 text-primary">Valider la commande</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Checkout Page End -->
@endsection

