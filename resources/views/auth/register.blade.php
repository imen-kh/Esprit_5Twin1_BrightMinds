@extends('layouts.back-blank')

@section('title', 'Inscription')

@section('content')
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Inscription -->
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
              <h4 class="mb-1">Rejoignez NutriTrace 🚀</h4>
              <p class="mb-6">Créez votre compte pour suivre vos produits de la ferme à l'assiette.</p>

              {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
              <form id="formAuthentication" class="mb-6" method="POST" action="{{ route('register.submit') }}" novalidate>
                @csrf
                <div class="mb-6">
                  <label for="name" class="form-label">Nom complet</label>
                  <input
                    type="text"
                    class="form-control @error('name') is-invalid @enderror"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Entrez votre nom"
                    autofocus />
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-6">
                  <label for="email" class="form-label">Adresse email</label>
                  <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Entrez votre adresse email" />
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="mb-6">
                  <label for="role" class="form-label">Vous êtes</label>
                  <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                    <option value="consommateur" @selected(old('role', 'consommateur') === 'consommateur')>Consommateur</option>
                    <option value="acteur" @selected(old('role') === 'acteur')>Acteur de la chaîne</option>
                  </select>
                  @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                {{-- Affiché seulement pour le rôle « acteur » (voir le script en bas de page) --}}
                <div class="mb-6 @if (old('role') !== 'acteur') d-none @endif" id="type-acteur-group">
                  <label for="type_acteur" class="form-label">Type d'acteur</label>
                  <select class="form-select @error('type_acteur') is-invalid @enderror" id="type_acteur" name="type_acteur">
                    <option value="">Choisissez votre type d'acteur</option>
                    <option value="producteur" @selected(old('type_acteur') === 'producteur')>Producteur</option>
                    <option value="transformateur" @selected(old('type_acteur') === 'transformateur')>Transformateur</option>
                    <option value="distributeur" @selected(old('type_acteur') === 'distributeur')>Distributeur</option>
                  </select>
                  @error('type_acteur')
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
                <button class="btn btn-primary d-grid w-100" type="submit">Créer mon compte</button>
              </form>

              <p class="text-center">
                <span>Vous avez déjà un compte ?</span>
                <a href="{{ route('login') }}">
                  <span>Se connecter</span>
                </a>
              </p>
            </div>
          </div>
          <!-- /Inscription -->
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
    // Montre le champ « Type d'acteur » uniquement quand le rôle « acteur » est choisi
    document.addEventListener('DOMContentLoaded', function () {
      const role = document.getElementById('role');
      const groupe = document.getElementById('type-acteur-group');
      const typeActeur = document.getElementById('type_acteur');

      function basculerTypeActeur() {
        const estActeur = role.value === 'acteur';
        groupe.classList.toggle('d-none', !estActeur);
        // Un consommateur n'envoie pas de type d'acteur
        typeActeur.disabled = !estActeur;
      }

      role.addEventListener('change', basculerTypeActeur);
      // Au rechargement après erreur, old('role') est déjà sélectionné : on applique le même état
      basculerTypeActeur();
    });
  </script>
@endpush
