@extends('layouts.back-blank')

@section('title', 'Vérification de la connexion')

@section('content')
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Double authentification -->
          <div class="card px-sm-6 px-0">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center mb-6">
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
              <h4 class="mb-1">Vérification en deux étapes 🔐</h4>
              <p class="mb-6">Nous venons de vous envoyer un code à 6 chiffres par email. Saisissez-le pour terminer votre connexion.</p>

              {{-- Confirmation du renvoi d'un code --}}
              @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
              @endif

              {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
              <form id="formAuthentication" class="mb-6" method="POST" action="{{ route('two-factor.verify') }}" novalidate>
                @csrf
                <div class="mb-6">
                  <label for="code" class="form-label">Code de vérification</label>
                  <input
                    type="text"
                    class="form-control text-center fs-4 @error('code') is-invalid @enderror"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus />
                  @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <button class="btn btn-primary d-grid w-100" type="submit">Vérifier et se connecter</button>
              </form>

              {{-- Renvoi d'un code (au plus un par minute) --}}
              <form class="text-center mb-4" method="POST" action="{{ route('two-factor.resend') }}">
                @csrf
                <span>Vous n'avez rien reçu ?</span>
                <button type="submit" class="btn btn-link p-0 align-baseline">Renvoyer le code</button>
              </form>

              <div class="text-center">
                <a href="{{ route('login') }}" class="d-flex justify-content-center">
                  <i class="icon-base bx bx-chevron-left me-1"></i>
                  Retour à la connexion
                </a>
              </div>
            </div>
          </div>
          <!-- /Double authentification -->
        </div>
      </div>
    </div>

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-auth.css') }}" />
@endpush
