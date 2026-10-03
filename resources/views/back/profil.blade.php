@extends('layouts.back')

@section('title', 'Mon profil')

@section('content')
              <div class="row">
                <div class="col-md-12">
                  {{-- Profil de l'administrateur (acteurs et consommateurs utilisent front/profil.blade.php) --}}
                  {{-- ===== Carte 1 : informations du profil ===== --}}
                  <div class="card mb-6">
                    <h5 class="card-header">Informations du profil</h5>
                    {{-- novalidate : on laisse la validation serveur (Form Request) afficher les erreurs --}}
                    <form
                      id="formProfil"
                      method="POST"
                      action="{{ route('profil.update') }}"
                      enctype="multipart/form-data"
                      novalidate>
                      @csrf
                      @method('PUT')

                      <!-- Avatar -->
                      <div class="card-body pt-0">
                        <div class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
                          <img
                            src="{{ $user->avatar_url }}"
                            alt="Avatar de {{ $user->name }}"
                            class="d-block w-px-100 h-px-100 rounded"
                            style="object-fit: cover;"
                            id="apercuAvatar" />
                          <div class="button-wrapper">
                            <label for="avatar" class="btn btn-primary me-3 mb-4" tabindex="0">
                              <span class="d-none d-sm-block">Changer la photo</span>
                              <i class="icon-base bx bx-upload d-block d-sm-none"></i>
                              <input
                                type="file"
                                id="avatar"
                                name="avatar"
                                class="@error('avatar') is-invalid @enderror"
                                hidden
                                accept="image/png, image/jpeg" />
                            </label>

                            <div>Formats acceptés : JPG ou PNG. Taille maximale : 2 Mo.</div>
                            @error('avatar')
                              <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                      </div>
                      <!-- /Avatar -->

                      <div class="card-body pt-4">
                        <div class="row g-6">
                          <div class="col-md-6">
                            <label for="name" class="form-label">Nom complet</label>
                            <input
                              class="form-control @error('name') is-invalid @enderror"
                              type="text"
                              id="name"
                              name="name"
                              value="{{ old('name', $user->name) }}" />
                            @error('name')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                          <div class="col-md-6">
                            <label for="email" class="form-label">Adresse email</label>
                            <input
                              class="form-control @error('email') is-invalid @enderror"
                              type="email"
                              id="email"
                              name="email"
                              value="{{ old('email', $user->email) }}" />
                            @error('email')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="telephone">Téléphone</label>
                            <div class="input-group input-group-merge">
                              <span class="input-group-text">TN (+216)</span>
                              <input
                                type="text"
                                id="telephone"
                                name="telephone"
                                class="form-control @error('telephone') is-invalid @enderror"
                                value="{{ old('telephone', $user->telephone) }}"
                                placeholder="22123456"
                                maxlength="8" />
                            </div>
                            @error('telephone')
                              <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>


                        <div class="mt-6">
                          <button type="submit" class="btn btn-primary me-3">Enregistrer</button>
                          <a href="{{ route('profil.edit') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                      </div>
                    </form>
                  </div>

                  {{-- ===== Carte 2 : changer le mot de passe ===== --}}
                  <div class="card mb-6">
                    <h5 class="card-header">Changer le mot de passe</h5>
                    <div class="card-body">
                      {{-- Les erreurs de ce formulaire sont dans le sac « motDePasse » --}}
                      <form id="formMotDePasse" method="POST" action="{{ route('profil.password') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row g-6">
                          <div class="col-md-6">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input
                              type="password"
                              class="form-control @error('current_password', 'motDePasse') is-invalid @enderror"
                              id="current_password"
                              name="current_password" />
                            @error('current_password', 'motDePasse')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                        <div class="row g-6 mt-0">
                          <div class="col-md-6">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input
                              type="password"
                              class="form-control @error('password', 'motDePasse') is-invalid @enderror"
                              id="password"
                              name="password" />
                            @error('password', 'motDePasse')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                          <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input
                              type="password"
                              class="form-control @error('password_confirmation', 'motDePasse') is-invalid @enderror"
                              id="password_confirmation"
                              name="password_confirmation" />
                            @error('password_confirmation', 'motDePasse')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                        <div class="mt-6">
                          <button type="submit" class="btn btn-primary">Mettre à jour le mot de passe</button>
                        </div>
                      </form>
                    </div>
                  </div>

                  {{-- ===== Carte 3 : double authentification (erreurs dans le sac « deuxFacteurs ») ===== --}}
                  <div class="card mb-6">
                    <h5 class="card-header">
                      Double authentification
                      @if ($user->two_factor_enabled)
                        <span class="badge bg-label-success ms-2">Activée</span>
                      @else
                        <span class="badge bg-label-secondary ms-2">Désactivée</span>
                      @endif
                    </h5>
                    <div class="card-body">
                      <p class="mb-6">
                        Lorsqu'elle est activée, un code à 6 chiffres est envoyé à <strong>{{ $user->email }}</strong> à chaque connexion, en plus de votre mot de passe.
                      </p>
                      <form id="formDoubleAuth" method="POST" action="{{ route('profil.two-factor') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row g-6">
                          <div class="col-md-6">
                            <label for="two_factor_password" class="form-label">Confirmez avec votre mot de passe</label>
                            <input
                              type="password"
                              class="form-control @error('password', 'deuxFacteurs') is-invalid @enderror"
                              id="two_factor_password"
                              name="password" />
                            @error('password', 'deuxFacteurs')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                        <div class="mt-6">
                          @if ($user->two_factor_enabled)
                            <button type="submit" class="btn btn-danger">Désactiver la double authentification</button>
                          @else
                            <button type="submit" class="btn btn-primary">Activer la double authentification</button>
                          @endif
                        </div>
                      </form>
                    </div>
                  </div>

                </div>
              </div>
@endsection

@push('scripts')
  <script>
    // Aperçu immédiat de l'avatar choisi, avant l'envoi du formulaire
    document.getElementById('avatar').addEventListener('change', function () {
      if (this.files && this.files[0]) {
        document.getElementById('apercuAvatar').src = URL.createObjectURL(this.files[0]);
      }
    });
  </script>
@endpush
