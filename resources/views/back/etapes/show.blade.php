@extends('layouts.back')

@section('title', $etape->titre)

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.etapes.index') }}">Étapes</a></li>
                  <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($etape->titre, 40) }}</li>
                </ol>
              </nav>

              <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
                <div class="d-flex align-items-center gap-4">
                  <span class="avatar avatar-md">
                    <span class="avatar-initial rounded bg-label-{{ $etape->type['couleur'] }}">
                      <i class="icon-base bx {{ $etape->type['icone'] }} icon-md"></i>
                    </span>
                  </span>
                  <div>
                    <h4 class="mb-1">{{ $etape->titre }}</h4>
                    <span class="badge bg-label-{{ $etape->type['couleur'] }}">{{ $etape->type['label'] }}</span>
                  </div>
                </div>
                {{-- Modifier et supprimer : auteur de l'étape ou admin --}}
                @if ($peutModifier)
                  <div class="d-flex gap-2">
                    <a href="{{ route('gestion.etapes.edit', $etape) }}" class="btn btn-outline-primary">
                      <i class="icon-base bx bx-edit-alt me-1"></i> Modifier
                    </a>
                    <button
                      type="button"
                      class="btn btn-outline-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#modalSuppression"
                      data-action="{{ route('gestion.etapes.destroy', $etape) }}"
                      data-libelle="l'étape « {{ $etape->titre }} »">
                      <i class="icon-base bx bx-trash me-1"></i> Supprimer
                    </button>
                  </div>
                @endif
              </div>

              <div class="row g-6">
                <div class="col-lg-7">
                  <div class="card h-100">
                    <h5 class="card-header">Détail de l'étape</h5>
                    <div class="card-body">
                      <dl class="row mb-0">
                        <dt class="col-sm-4 text-body-secondary fw-normal">Date et heure</dt>
                        <dd class="col-sm-8">{{ $etape->date_etape->format('d/m/Y à H:i') }}</dd>

                        <dt class="col-sm-4 text-body-secondary fw-normal">Lieu</dt>
                        <dd class="col-sm-8">{{ $etape->lieu }}</dd>

                        <dt class="col-sm-4 text-body-secondary fw-normal">Coordonnées GPS</dt>
                        <dd class="col-sm-8">
                          @if ($etape->latitude !== null && $etape->longitude !== null)
                            {{ (float) $etape->latitude }}, {{ (float) $etape->longitude }}
                          @else
                            Non renseignées
                          @endif
                        </dd>

                        <dt class="col-sm-4 text-body-secondary fw-normal">Température</dt>
                        <dd class="col-sm-8">{{ $etape->temperature_formatee ?? 'Non renseignée' }}</dd>

                        <dt class="col-sm-4 text-body-secondary fw-normal">Description</dt>
                        <dd class="col-sm-8 mb-0">{{ $etape->description ?: 'Aucune' }}</dd>
                      </dl>
                    </div>
                  </div>
                </div>

                <div class="col-lg-5">
                  <div class="card mb-6">
                    <h5 class="card-header">Lot concerné</h5>
                    <div class="card-body">
                      <p class="mb-1">
                        {{-- Lien seulement si l'utilisateur a le droit de consulter le lot --}}
                        @if ($lotVisible)
                          <a href="{{ route('gestion.lots.show', $etape->lot) }}" class="fw-medium">{{ $etape->lot->code }}</a>
                        @else
                          <span class="fw-medium">{{ $etape->lot->code }}</span>
                        @endif
                        <span class="badge bg-label-{{ $etape->lot->statut_badge }} ms-1">{{ $etape->lot->statut_label }}</span>
                      </p>
                      <p class="mb-0 text-body-secondary">{{ $etape->lot->produit->name }}</p>
                    </div>
                  </div>

                  <div class="card">
                    <h5 class="card-header">Réalisée par</h5>
                    <div class="card-body">
                      <div class="d-flex align-items-center gap-4">
                        <div class="avatar">
                          <img src="{{ $etape->acteur?->avatar_url }}" alt class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}</h6>
                          <small class="text-body-secondary">
                            {{ ucfirst((string) ($etape->acteur?->type_acteur ?? $etape->acteur?->role)) }}@if ($etape->acteur?->ville) · {{ $etape->acteur->ville }}@endif
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              @include('back.partials.modal-suppression')
@endsection
