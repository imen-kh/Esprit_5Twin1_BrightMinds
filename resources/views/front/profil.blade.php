@extends('layouts.front')

@section('title', 'Mon profil')

@section('content')
        {{-- Bandeau du template : titre + fil d'Ariane Accueil > Mon profil --}}
        @include('partials.front.page-header', ['titre' => 'Mon profil'])

        <!-- Profil Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">
                <div class="row g-5">

                    {{-- ================= Colonne gauche : carte de l'utilisateur ================= --}}
                    <div class="col-lg-4">
                        <div class="bg-light rounded p-4 text-center profil-carte">
                            <img
                                src="{{ $user->avatar_url }}"
                                alt="Avatar de {{ $user->name }}"
                                class="rounded-circle border border-3 border-secondary mb-3 apercu-avatar"
                                style="width: 130px; height: 130px; object-fit: cover;">
                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="text-muted mb-3">{{ $user->email }}</p>

                            {{-- Badge du rôle, et du type d'acteur pour un acteur --}}
                            <div class="mb-3">
                                <span class="badge bg-primary rounded-pill px-3 py-2">{{ ucfirst($user->role) }}</span>
                                @if ($user->isActeur() && $user->type_acteur)
                                    <span class="badge bg-secondary rounded-pill px-3 py-2">{{ ucfirst($user->type_acteur) }}</span>
                                @endif
                            </div>

                            <p class="mb-4">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>Inscrit le {{ $user->created_at->format('d/m/Y') }}
                            </p>

                            {{-- Liens d'ancre vers les sections de droite --}}
                            <div class="list-group list-group-flush text-start rounded mb-4">
                                <a href="#informations" class="list-group-item list-group-item-action bg-transparent">
                                    <i class="fas fa-user text-primary me-2"></i>Informations personnelles
                                </a>
                                @if ($user->isActeur())
                                    <a href="#entreprise" class="list-group-item list-group-item-action bg-transparent">
                                        <i class="fas fa-store text-primary me-2"></i>Mon entreprise
                                    </a>
                                @else
                                    <a href="#preferences" class="list-group-item list-group-item-action bg-transparent">
                                        <i class="fas fa-heart text-primary me-2"></i>Mes préférences
                                    </a>
                                @endif
                                <a href="#mot-de-passe" class="list-group-item list-group-item-action bg-transparent">
                                    <i class="fas fa-lock text-primary me-2"></i>Changer le mot de passe
                                </a>
                                <a href="#double-authentification" class="list-group-item list-group-item-action bg-transparent">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>Double authentification
                                </a>
                                <a href="#suppression" class="list-group-item list-group-item-action bg-transparent">
                                    <i class="fas fa-trash-alt text-primary me-2"></i>Supprimer mon compte
                                </a>
                            </div>

                            {{-- Accès à l'espace de gestion de l'acteur (module Traçabilité : gestion des lots) --}}
                            @if ($user->isActeur())
                                @if (Route::has('gestion.lots.index'))
                                    <a href="{{ route('gestion.lots.index') }}" class="btn btn-primary rounded-pill text-white w-100 py-3">
                                        <i class="fas fa-briefcase me-2"></i>Accéder à mon espace acteur
                                    </a>
                                @else
                                    <a href="#" class="btn btn-primary rounded-pill text-white w-100 py-3 disabled" aria-disabled="true">
                                        <i class="fas fa-briefcase me-2"></i>Accéder à mon espace acteur
                                    </a>
                                    <small class="text-muted d-block mt-2">Espace acteur bientôt disponible.</small>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- ================= Colonne droite : formulaires ================= --}}
                    <div class="col-lg-8">
                        {{-- Message flash de succès --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            </div>
                        @endif

                        {{-- Formulaire du profil : blocs a) puis b) ou c). novalidate : la validation serveur affiche les erreurs --}}
                        <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" novalidate>
                            @csrf
                            @method('PUT')

                            {{-- ===== a) Informations personnelles ===== --}}
                            <div class="bg-light rounded p-4 mb-4 profil-section" id="informations">
                                <h4 class="text-primary mb-4">Informations personnelles</h4>

                                <div class="d-flex align-items-center mb-4">
                                    <img
                                        src="{{ $user->avatar_url }}"
                                        alt="Aperçu de l'avatar"
                                        class="rounded-circle me-4 apercu-avatar"
                                        style="width: 80px; height: 80px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <label for="avatar" class="form-label">Photo de profil</label>
                                        <input
                                            type="file"
                                            class="form-control @error('avatar') is-invalid @else border-0 @enderror"
                                            id="avatar"
                                            name="avatar"
                                            accept="image/png, image/jpeg">
                                        @error('avatar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Formats acceptés : JPG ou PNG. Taille maximale : 2 Mo.</small>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nom complet<sup>*</sup></label>
                                        <input
                                            type="text"
                                            class="form-control py-3 @error('name') is-invalid @else border-0 @enderror"
                                            id="name"
                                            name="name"
                                            value="{{ old('name', $user->name) }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Adresse email<sup>*</sup></label>
                                        <input
                                            type="email"
                                            class="form-control py-3 @error('email') is-invalid @else border-0 @enderror"
                                            id="email"
                                            name="email"
                                            value="{{ old('email', $user->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone (+216)</label>
                                        <input
                                            type="tel"
                                            class="form-control py-3 @error('telephone') is-invalid @else border-0 @enderror"
                                            id="telephone"
                                            name="telephone"
                                            value="{{ old('telephone', $user->telephone) }}"
                                            placeholder="22123456"
                                            maxlength="8">
                                        @error('telephone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @if ($user->isActeur())
                                {{-- ===== b) Mon entreprise (acteur) ===== --}}
                                <div class="bg-light rounded p-4 mb-4 profil-section" id="entreprise">
                                    <h4 class="text-primary mb-4">Mon entreprise</h4>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label for="type_acteur" class="form-label">Type d'acteur<sup>*</sup></label>
                                            <select
                                                class="form-select py-3 @error('type_acteur') is-invalid @else border-0 @enderror"
                                                id="type_acteur"
                                                name="type_acteur">
                                                <option value="">Choisissez votre type d'acteur</option>
                                                @foreach (['producteur' => 'Producteur', 'transformateur' => 'Transformateur', 'distributeur' => 'Distributeur'] as $valeur => $libelle)
                                                    <option value="{{ $valeur }}" @selected(old('type_acteur', $user->type_acteur) === $valeur)>{{ $libelle }}</option>
                                                @endforeach
                                            </select>
                                            @error('type_acteur')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nom_entreprise" class="form-label">Nom de l'entreprise<sup>*</sup></label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('nom_entreprise') is-invalid @else border-0 @enderror"
                                                id="nom_entreprise"
                                                name="nom_entreprise"
                                                value="{{ old('nom_entreprise', $user->nom_entreprise) }}">
                                            @error('nom_entreprise')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-8">
                                            <label for="adresse" class="form-label">Adresse<sup>*</sup></label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('adresse') is-invalid @else border-0 @enderror"
                                                id="adresse"
                                                name="adresse"
                                                value="{{ old('adresse', $user->adresse) }}">
                                            @error('adresse')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="ville" class="form-label">Ville<sup>*</sup></label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('ville') is-invalid @else border-0 @enderror"
                                                id="ville"
                                                name="ville"
                                                value="{{ old('ville', $user->ville) }}">
                                            @error('ville')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="matricule_fiscal" class="form-label">Matricule fiscal</label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('matricule_fiscal') is-invalid @else border-0 @enderror"
                                                id="matricule_fiscal"
                                                name="matricule_fiscal"
                                                value="{{ old('matricule_fiscal', $user->matricule_fiscal) }}"
                                                placeholder="1234567/A/M/000">
                                            @error('matricule_fiscal')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="site_web" class="form-label">Site web</label>
                                            <input
                                                type="url"
                                                class="form-control py-3 @error('site_web') is-invalid @else border-0 @enderror"
                                                id="site_web"
                                                name="site_web"
                                                value="{{ old('site_web', $user->site_web) }}"
                                                placeholder="https://exemple.tn">
                                            @error('site_web')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea
                                                class="form-control @error('description') is-invalid @else border-0 @enderror"
                                                id="description"
                                                name="description"
                                                rows="4"
                                                placeholder="Présentez votre activité en quelques lignes">{{ old('description', $user->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('latitude') is-invalid @else border-0 @enderror"
                                                id="latitude"
                                                name="latitude"
                                                value="{{ old('latitude', $user->latitude) }}"
                                                placeholder="36.45">
                                            @error('latitude')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('longitude') is-invalid @else border-0 @enderror"
                                                id="longitude"
                                                name="longitude"
                                                value="{{ old('longitude', $user->longitude) }}"
                                                placeholder="10.73">
                                            @error('longitude')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- ===== c) Mes préférences (consommateur) ===== --}}
                                <div class="bg-light rounded p-4 mb-4 profil-section" id="preferences">
                                    <h4 class="text-primary mb-4">Mes préférences</h4>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label for="ville" class="form-label">Ville</label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('ville') is-invalid @else border-0 @enderror"
                                                id="ville"
                                                name="ville"
                                                value="{{ old('ville', $user->ville) }}">
                                            @error('ville')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="allergies" class="form-label">Allergies</label>
                                            <input
                                                type="text"
                                                class="form-control py-3 @error('allergies') is-invalid @else border-0 @enderror"
                                                id="allergies"
                                                name="allergies"
                                                value="{{ old('allergies', $user->allergies) }}"
                                                placeholder="Ex. gluten, arachides">
                                            @error('allergies')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label d-block">Je privilégie les produits</label>
                                            {{-- Cochées selon old() après une erreur de ce formulaire, sinon selon les préférences enregistrées --}}
                                            @php
                                                $preferences = $errors->any() ? old('preferences', []) : ($user->preferences ?? []);
                                            @endphp
                                            @foreach (['bio' => 'Bio', 'local' => 'Local', 'equitable' => 'Équitable'] as $valeur => $libelle)
                                                <div class="form-check form-check-inline">
                                                    <input
                                                        class="form-check-input @error('preferences') is-invalid @enderror @error('preferences.*') is-invalid @enderror"
                                                        type="checkbox"
                                                        id="preference_{{ $valeur }}"
                                                        name="preferences[]"
                                                        value="{{ $valeur }}"
                                                        @checked(in_array($valeur, $preferences))>
                                                    <label class="form-check-label" for="preference_{{ $valeur }}">{{ $libelle }}</label>
                                                </div>
                                            @endforeach
                                            @error('preferences')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @error('preferences.*')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-5">
                                <button class="btn border-secondary rounded-pill px-5 py-3 text-primary" type="submit">Enregistrer mon profil</button>
                            </div>
                        </form>

                        {{-- ===== d) Changer le mot de passe (erreurs dans le sac « motDePasse ») ===== --}}
                        <div class="bg-light rounded p-4 mb-4 profil-section" id="mot-de-passe">
                            <h4 class="text-primary mb-4">Changer le mot de passe</h4>
                            <form method="POST" action="{{ route('profil.password') }}" novalidate>
                                @csrf
                                @method('PUT')
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label for="current_password" class="form-label">Mot de passe actuel<sup>*</sup></label>
                                        <input
                                            type="password"
                                            class="form-control py-3 @error('current_password', 'motDePasse') is-invalid @else border-0 @enderror"
                                            id="current_password"
                                            name="current_password">
                                        @error('current_password', 'motDePasse')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Nouveau mot de passe<sup>*</sup></label>
                                        <input
                                            type="password"
                                            class="form-control py-3 @error('password', 'motDePasse') is-invalid @else border-0 @enderror"
                                            id="password"
                                            name="password">
                                        @error('password', 'motDePasse')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe<sup>*</sup></label>
                                        <input
                                            type="password"
                                            class="form-control py-3 @error('password_confirmation', 'motDePasse') is-invalid @else border-0 @enderror"
                                            id="password_confirmation"
                                            name="password_confirmation">
                                        @error('password_confirmation', 'motDePasse')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <button class="btn border-secondary rounded-pill px-5 py-3 text-primary mt-4" type="submit">Mettre à jour le mot de passe</button>
                            </form>
                        </div>

                        {{-- ===== Double authentification (erreurs dans le sac « deuxFacteurs ») ===== --}}
                        <div class="bg-light rounded p-4 mb-4 profil-section" id="double-authentification">
                            <h4 class="text-primary mb-3">
                                Double authentification
                                @if ($user->aDoubleAuthentification())
                                    <span class="badge bg-primary rounded-pill fs-6 ms-2">Activée</span>
                                @elseif ($user->doubleAuthentificationEnAttente())
                                    <span class="badge bg-warning rounded-pill fs-6 ms-2">En cours d'activation</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill fs-6 ms-2">Désactivée</span>
                                @endif
                            </h4>
                            @include('partials.double-authentification', ['theme' => 'front'])
                        </div>

                        {{-- ===== e) Supprimer mon compte (erreurs dans le sac « suppression ») ===== --}}
                        <div class="bg-light rounded p-4 mb-4 profil-section" id="suppression">
                            <h4 class="text-danger mb-3">Supprimer mon compte</h4>
                            <p class="mb-4">Cette action est définitive : toutes vos données seront supprimées.</p>
                            <form
                                method="POST"
                                action="{{ route('profil.destroy') }}"
                                onsubmit="return confirm('Confirmez-vous la suppression définitive de votre compte ?');"
                                novalidate>
                                @csrf
                                @method('DELETE')
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="suppression_password" class="form-label">Confirmez avec votre mot de passe<sup>*</sup></label>
                                        <input
                                            type="password"
                                            class="form-control py-3 @error('password', 'suppression') is-invalid @else border-0 @enderror"
                                            id="suppression_password"
                                            name="password">
                                        @error('password', 'suppression')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <button class="btn btn-danger rounded-pill px-5 py-3 mt-4" type="submit">Supprimer mon compte</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Profil End -->
@endsection

@push('styles')
    <style>
        /* La navbar du template est fixe : on décale l'arrivée des liens d'ancre */
        .profil-section { scroll-margin-top: 160px; }

        /* Sur grand écran, la carte de gauche reste visible pendant le défilement */
        @media (min-width: 992px) {
            .profil-carte { position: sticky; top: 160px; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Aperçu immédiat de l'avatar choisi (carte de gauche et formulaire), avant l'envoi
        document.getElementById('avatar').addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const url = URL.createObjectURL(this.files[0]);
                document.querySelectorAll('.apercu-avatar').forEach(function (image) {
                    image.src = url;
                });
            }
        });
    </script>
@endpush
