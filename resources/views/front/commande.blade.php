@extends('layouts.front')

@section('title', 'Commande')

@section('content')
        <!-- Single Page Header start -->
        <div class="container-fluid page-header py-5">
            <h1 class="text-center text-white display-6">Commande</h1>
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#">Accueil</a></li>
                <li class="breadcrumb-item"><a href="#">Pages</a></li>
                <li class="breadcrumb-item active text-white">Commande</li>
            </ol>
        </div>
        <!-- Single Page Header End -->


        <!-- Checkout Page Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">
                <h1 class="mb-4">Informations de facturation</h1>
                <form action="#">
                    <div class="row g-5">
                        <div class="col-md-12 col-lg-6 col-xl-7">
                            <div class="row">
                                <div class="col-md-12 col-lg-6">
                                    <div class="form-item w-100">
                                        <label class="form-label my-3">Prénom<sup>*</sup></label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12 col-lg-6">
                                    <div class="form-item w-100">
                                        <label class="form-label my-3">Nom<sup>*</sup></label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Nom de l'entreprise<sup>*</sup></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Adresse <sup>*</sup></label>
                                <input type="text" class="form-control" placeholder="Numéro et nom de la rue">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Ville<sup>*</sup></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Pays<sup>*</sup></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Code postal<sup>*</sup></label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Téléphone<sup>*</sup></label>
                                <input type="tel" class="form-control">
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Adresse email<sup>*</sup></label>
                                <input type="email" class="form-control">
                            </div>
                            <div class="form-check my-3">
                                <input type="checkbox" class="form-check-input" id="Account-1" name="Accounts" value="Accounts">
                                <label class="form-check-label" for="Account-1">Créer un compte ?</label>
                            </div>
                            <hr>
                            <div class="form-check my-3">
                                <input class="form-check-input" type="checkbox" id="Address-1" name="Address" value="Address">
                                <label class="form-check-label" for="Address-1">Livrer à une autre adresse ?</label>
                            </div>
                            <div class="form-item">
                                <textarea name="text" class="form-control" spellcheck="false" cols="30" rows="11" placeholder="Notes de commande (facultatif)"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6 col-xl-5">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Produits</th>
                                            <th scope="col">Nom</th>
                                            <th scope="col">Prix</th>
                                            <th scope="col">Quantité</th>
                                            <th scope="col">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <img src="{{ asset('front/img/vegetable-item-2.jpg') }}" class="img-fluid rounded-circle" style="width: 90px; height: 90px;" alt="">
                                                </div>
                                            </th>
                                            <td class="py-5">Brocoli frais</td>
                                            <td class="py-5">69,00 DT</td>
                                            <td class="py-5">2</td>
                                            <td class="py-5">138,00 DT</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <img src="{{ asset('front/img/vegetable-item-5.jpg') }}" class="img-fluid rounded-circle" style="width: 90px; height: 90px;" alt="">
                                                </div>
                                            </th>
                                            <td class="py-5">Pommes de terre</td>
                                            <td class="py-5">69,00 DT</td>
                                            <td class="py-5">2</td>
                                            <td class="py-5">138,00 DT</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <img src="{{ asset('front/img/vegetable-item-3.png') }}" class="img-fluid rounded-circle" style="width: 90px; height: 90px;" alt="">
                                                </div>
                                            </th>
                                            <td class="py-5">Banane</td>
                                            <td class="py-5">69,00 DT</td>
                                            <td class="py-5">2</td>
                                            <td class="py-5">138,00 DT</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                            </th>
                                            <td class="py-5"></td>
                                            <td class="py-5"></td>
                                            <td class="py-5">
                                                <p class="mb-0 text-dark py-3">Sous-total</p>
                                            </td>
                                            <td class="py-5">
                                                <div class="py-3 border-bottom border-top">
                                                    <p class="mb-0 text-dark">414,00 DT</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                            </th>
                                            <td class="py-5">
                                                <p class="mb-0 text-dark py-4">Livraison</p>
                                            </td>
                                            <td colspan="3" class="py-5">
                                                <div class="form-check text-start">
                                                    <input type="checkbox" class="form-check-input bg-primary border-0" id="Shipping-1" name="Shipping-1" value="Shipping">
                                                    <label class="form-check-label" for="Shipping-1">Livraison gratuite</label>
                                                </div>
                                                <div class="form-check text-start">
                                                    <input type="checkbox" class="form-check-input bg-primary border-0" id="Shipping-2" name="Shipping-1" value="Shipping">
                                                    <label class="form-check-label" for="Shipping-2">Forfait : 15,00 DT</label>
                                                </div>
                                                <div class="form-check text-start">
                                                    <input type="checkbox" class="form-check-input bg-primary border-0" id="Shipping-3" name="Shipping-1" value="Shipping">
                                                    <label class="form-check-label" for="Shipping-3">Retrait sur place : 8,00 DT</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                            </th>
                                            <td class="py-5">
                                                <p class="mb-0 text-dark text-uppercase py-3">TOTAL</p>
                                            </td>
                                            <td class="py-5"></td>
                                            <td class="py-5"></td>
                                            <td class="py-5">
                                                <div class="py-3 border-bottom border-top">
                                                    <p class="mb-0 text-dark">135,00 DT</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                                <div class="col-12">
                                    <div class="form-check text-start my-3">
                                        <input type="checkbox" class="form-check-input bg-primary border-0" id="Transfer-1" name="Transfer" value="Transfer">
                                        <label class="form-check-label" for="Transfer-1">Virement bancaire</label>
                                    </div>
                                    <p class="text-start text-dark">Effectuez votre paiement directement sur notre compte bancaire, en indiquant le numéro de commande comme référence. La commande est expédiée dès réception des fonds.</p>
                                </div>
                            </div>
                            <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                                <div class="col-12">
                                    <div class="form-check text-start my-3">
                                        <input type="checkbox" class="form-check-input bg-primary border-0" id="Payments-1" name="Payments" value="Payments">
                                        <label class="form-check-label" for="Payments-1">Paiement par chèque</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                                <div class="col-12">
                                    <div class="form-check text-start my-3">
                                        <input type="checkbox" class="form-check-input bg-primary border-0" id="Delivery-1" name="Delivery" value="Delivery">
                                        <label class="form-check-label" for="Delivery-1">Paiement à la livraison</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                                <div class="col-12">
                                    <div class="form-check text-start my-3">
                                        <input type="checkbox" class="form-check-input bg-primary border-0" id="Paypal-1" name="Paypal" value="Paypal">
                                        <label class="form-check-label" for="Paypal-1">Paypal</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                                <button type="button" class="btn border-secondary py-3 px-4 text-uppercase w-100 text-primary">Valider la commande</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Checkout Page End -->
@endsection
