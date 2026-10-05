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
                            @endphp
                            <tr>
                                <th scope="row">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ asset('front/img/vegetable-item-3.png') }}" class="img-fluid me-5 rounded-circle" style="width: 80px; height: 80px;" alt="">
                                    </div>
                                </th>
                                <td>
                                    <p class="mb-0 mt-4">{{ $product->name }}</p>
                                </td>
                                <td>
                                    <p class="mb-0 mt-4">{{ number_format($product->price, 3, ',', ' ') }} DT</p>
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
                                    <p class="mb-0 mt-4">{{ number_format($total_produit, 3, ',', ' ') }} DT</p>
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
                            <p class="mb-0">{{ number_format($sous_total, 3, ',', ' ') }} DT</p>
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
                        <p class="mb-0 pe-4">{{ number_format($sous_total + ($sous_total > 0 ? 3 : 0), 3, ',', ' ') }} DT</p>
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
@endsection
