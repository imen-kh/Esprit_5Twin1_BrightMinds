@extends('layouts.back-blank')

@section('title', 'Nouveau mot de passe')

@section('content')
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Nouveau mot de passe -->
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
              <h4 class="mb-1">Nouveau mot de passe 🔑</h4>
              <p class="mb-6">Choisissez un nouveau mot de passe pour votre compte.</p>

              {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
              <form id="formAuthentication" class="mb-6" method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                {{-- Jeton reçu dans le lien de l'email --}}
                <input type="hidden" name="token" value="{{ $token }}" />
                @error('token')
                  <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <div class="mb-6">
                  <label for="email" class="form-label">Adresse email</label>
                  <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    placeholder="Entrez votre adresse email" />
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-6 form-password-toggle">
                  <label class="form-label" for="password">Nouveau mot de passe</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control @error('password') is-invalid @enderror"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password"
                      autofocus />
                    <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  </div>
                  @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-6 form-password-toggle">
                  <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password_confirmation"
                      class="form-control @error('password_confirmation') is-invalid @enderror"
                      name="password_confirmation"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password_confirmation" />
                    <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  </div>
                  @error('password_confirmation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <button class="btn btn-primary d-grid w-100" type="submit">Réinitialiser le mot de passe</button>
              </form>

              <div class="text-center">
                <a href="{{ route('login') }}" class="d-flex justify-content-center">
                  <i class="icon-base bx bx-chevron-left me-1"></i>
                  Retour à la connexion
                </a>
              </div>
            </div>
          </div>
          <!-- /Nouveau mot de passe -->
        </div>
      </div>
    </div>

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-auth.css') }}" />
@endpush
