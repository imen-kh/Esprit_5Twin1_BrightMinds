@extends('layouts.front')

@section('title', 'Inscription')

@section('content')
@include('partials.front.page-header', ['titre' => 'Inscription'])

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="bg-light rounded p-4 p-md-5">
                <h2 class="text-primary mb-2">Créer un compte</h2>
                <p class="mb-4">Inscrivez-vous pour consulter les produits, le panier et les témoignages.</p>

                <form method="POST" action="{{ route('register.submit') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Nom complet</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Votre nom" autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Adresse email</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nom@exemple.tn">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Vous êtes</label>
                        <select class="form-select border-secondary rounded-pill py-3 px-4 @error('role') is-invalid @enderror" id="role" name="role">
                            <option value="consommateur" @selected(old('role', 'consommateur') === 'consommateur')>Consommateur</option>
                            <option value="acteur" @selected(old('role') === 'acteur')>Acteur de la chaîne</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 @if (old('role') !== 'acteur') d-none @endif" id="type-acteur-group">
                        <label class="form-label" for="type_acteur">Type d'acteur</label>
                        <select class="form-select border-secondary rounded-pill py-3 px-4 @error('type_acteur') is-invalid @enderror" id="type_acteur" name="type_acteur">
                            <option value="">Choisissez votre type d'acteur</option>
                            <option value="producteur" @selected(old('type_acteur') === 'producteur')>Producteur</option>
                            <option value="transformateur" @selected(old('type_acteur') === 'transformateur')>Transformateur</option>
                            <option value="distributeur" @selected(old('type_acteur') === 'distributeur')>Distributeur</option>
                        </select>
                        @error('type_acteur')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('password') is-invalid @enderror" id="password" name="password" type="password" placeholder="Au moins 8 caractères">
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" type="password" placeholder="Répétez le mot de passe">
                        @error('password_confirmation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary rounded-pill w-100 py-3" type="submit">Créer mon compte</button>
                </form>

                <p class="text-center mt-4 mb-0">Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    const groupe = document.getElementById('type-acteur-group');
    const typeActeur = document.getElementById('type_acteur');

    function basculerTypeActeur() {
        const estActeur = role.value === 'acteur';
        groupe.classList.toggle('d-none', !estActeur);
        typeActeur.disabled = !estActeur;
    }

    role.addEventListener('change', basculerTypeActeur);
    basculerTypeActeur();
});
</script>
@endpush
