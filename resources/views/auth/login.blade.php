@extends('layouts.front')

@section('title', 'Connexion')

@section('content')
@include('partials.front.page-header', ['titre' => 'Connexion'])

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="bg-light rounded p-4 p-md-5">
                <h2 class="text-primary mb-2">Bienvenue</h2>
                <p class="mb-4">Connectez-vous pour accéder aux produits NutriTrace.</p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Adresse email</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nom@exemple.tn" autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input class="form-control border-secondary rounded-pill py-3 px-4 @error('password') is-invalid @enderror" id="password" name="password" type="password" placeholder="Votre mot de passe">
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                    </div>
                    <button class="btn btn-primary rounded-pill w-100 py-3" type="submit">Se connecter</button>
                </form>

                <p class="text-center mt-4 mb-0">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
