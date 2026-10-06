@extends('layouts.back-blank')

@section('title', 'Connexion')

@section('content')
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Connexion -->
          <div class="card px-sm-6 px-0">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="{{ route('home') }}" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                    <span class="text-primary">
                      @include('partials.back.logo')
                    </span>
                  </span>
                  <span class="app-brand-text demo text-heading fw-bold">NutriTrace</span>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-1">Bienvenue sur NutriTrace ! 👋</h4>
              <p class="mb-6">Connectez-vous à votre compte pour continuer.</p>

              {{-- Message affiché après une réinitialisation de mot de passe réussie --}}
              @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
              @endif

              {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
              <form id="formAuthentication" class="mb-6" method="POST" action="{{ route('login.submit') }}" novalidate>
                @csrf
                <div class="mb-6">
                  <label for="email" class="form-label">Adresse email</label>
                  <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Entrez votre adresse email"
                    autofocus />
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-6 form-password-toggle">
                  <label class="form-label" for="password">Mot de passe</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control @error('password') is-invalid @enderror"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  </div>
                  @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-8">
                  <div class="d-flex justify-content-between">
                    <div class="form-check mb-0">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        @checked(old('remember')) />
                      <label class="form-check-label" for="remember"> Se souvenir de moi </label>
                    </div>
                    <a href="{{ route('password.request') }}">
                      <span>Mot de passe oublié ?</span>
                    </a>
                  </div>
                </div>
                <div class="mb-6">
                  <button class="btn btn-primary d-grid w-100" type="submit">Se connecter</button>
                </div>
              </form>

              <p class="text-center">
                <span>Pas encore de compte ?</span>
                <a href="{{ route('register') }}">
                  <span>Créer un compte</span>
                </a>
              </p>
            </div>
          </div>
          <!-- /Connexion -->
        </div>
      </div>
    </div>

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-auth.css') }}" />
@endpush
