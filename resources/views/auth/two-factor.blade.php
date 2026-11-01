@extends('layouts.back-blank')

@section('title', 'Vérification de la connexion')

@section('content')
    <!-- Content -->

    @php($parRecuperation = old('recovery_code') !== null || $errors->has('recovery_code'))

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

              {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
              <form id="formAuthentication" class="mb-6" method="POST" action="{{ route('two-factor.verify') }}" novalidate>
                @csrf

                {{-- Code de l'application d'authentification --}}
                <div id="bloc-code" @if ($parRecuperation) hidden @endif>
                  <p class="mb-6">Ouvrez votre application d'authentification (Google Authenticator…) et saisissez le code à 6 chiffres affiché pour NutriTrace.</p>
                  <div class="mb-6">
                    <label for="code" class="form-label">Code de l'application</label>
                    <input
                      type="text"
                      class="form-control text-center fs-4 @error('code') is-invalid @enderror"
                      id="code"
                      name="code"
                      placeholder="000000"
                      maxlength="7"
                      inputmode="numeric"
                      autocomplete="one-time-code"
                      @disabled($parRecuperation)
                      @unless ($parRecuperation) autofocus @endunless />
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                {{-- Code de récupération : en cas de téléphone perdu, valable une seule fois --}}
                <div id="bloc-recuperation" @unless ($parRecuperation) hidden @endunless>
                  <p class="mb-6">Saisissez l'un des codes de récupération obtenus lors de l'activation. Chaque code ne fonctionne qu'une seule fois.</p>
                  <div class="mb-6">
                    <label for="recovery_code" class="form-label">Code de récupération</label>
                    <input
                      type="text"
                      class="form-control text-center fs-5 text-uppercase @error('recovery_code') is-invalid @enderror"
                      id="recovery_code"
                      name="recovery_code"
                      value="{{ old('recovery_code') }}"
                      placeholder="XXXXX-XXXXX"
                      maxlength="11"
                      autocomplete="off"
                      @disabled(! $parRecuperation) />
                    @error('recovery_code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <button class="btn btn-primary d-grid w-100" type="submit">Vérifier et se connecter</button>
              </form>

              <div class="text-center mb-4">
                <button type="button" class="btn btn-link p-0" id="basculer-recuperation">
                  {{ $parRecuperation ? 'Utiliser le code de l\'application' : 'Utiliser un code de récupération' }}
                </button>
              </div>

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

@push('scripts')
  <script>
    // Bascule code de l'application <-> code de récupération : seul le champ visible est envoyé
    document.getElementById('basculer-recuperation').addEventListener('click', function () {
      const blocCode = document.getElementById('bloc-code');
      const blocRecuperation = document.getElementById('bloc-recuperation');
      const versRecuperation = ! blocCode.hidden;

      blocCode.hidden = versRecuperation;
      blocRecuperation.hidden = ! versRecuperation;
      document.getElementById('code').disabled = versRecuperation;
      document.getElementById('recovery_code').disabled = ! versRecuperation;
      this.textContent = versRecuperation ? 'Utiliser le code de l\'application' : 'Utiliser un code de récupération';
      document.getElementById(versRecuperation ? 'recovery_code' : 'code').focus();
    });
  </script>
@endpush
