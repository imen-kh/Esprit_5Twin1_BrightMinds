@extends('layouts.back')

@section('title', 'Étapes de traçabilité')

@section('content')
              @if (session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                  {{ session('error') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
              @endif

              <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
                <div>
                  <h4 class="mb-1">Étapes de traçabilité</h4>
                  <p class="mb-0 text-body-secondary">Chaque étape retrace un moment du parcours d'un lot.</p>
                </div>
                @if ($peutCreer)
                  <a href="{{ route('gestion.etapes.create') }}" class="btn btn-primary">
                    <i class="icon-base bx bx-plus me-1"></i> Nouvelle étape
                  </a>
                @endif
              </div>

              <div class="card">
                {{-- Recherche et filtres : formulaire GET, les critères restent dans l'URL et la pagination --}}
                <div class="card-header">
                  <form method="GET" action="{{ route('gestion.etapes.index') }}" class="row g-4 align-items-end">
                    <div class="col-md-4">
                      <label for="q" class="form-label">Rechercher</label>
                      <input
                        type="search"
                        class="form-control"
                        id="q"
                        name="q"
                        value="{{ $recherche }}"
                        placeholder="Titre, lieu, code du lot ou produit" />
                    </div>
                    <div class="col-md-3">
                      <label for="type_etape" class="form-label">Type d'étape</label>
                      <select class="form-select" id="type_etape" name="type_etape">
                        <option value="">Tous les types</option>
                        @foreach (\App\Models\EtapeTracabilite::TYPES as $cle => $infos)
                          <option value="{{ $cle }}" @selected($type === $cle)>{{ $infos['label'] }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="lot_id" class="form-label">Lot</label>
                      <select class="form-select" id="lot_id" name="lot_id">
                        <option value="">Tous les lots</option>
                        @foreach ($lots as $lot)
                          <option value="{{ $lot->id }}" @selected((string) $lotId === (string) $lot->id)>{{ $lot->code }} — {{ $lot->produit->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                      <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="icon-base bx bx-search me-1"></i> Filtrer
                      </button>
                      @if ($recherche !== '' || $type || $lotId)
                        <a href="{{ route('gestion.etapes.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                          <i class="icon-base bx bx-reset"></i>
                        </a>
                      @endif
                    </div>
                  </form>
                </div>

                <div class="table-responsive text-nowrap">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Étape</th>
                        <th>Lot</th>
                        <th>Lieu</th>
                        <th>Acteur</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      @forelse ($etapes as $etape)
                        <tr>
                          <td>{{ $etape->date_etape->format('d/m/Y H:i') }}</td>
                          <td>
                            <span class="badge bg-label-{{ $etape->type['couleur'] }}">
                              <i class="icon-base bx {{ $etape->type['icone'] }} icon-xs me-1"></i>{{ $etape->type['label'] }}
                            </span>
                          </td>
                          <td>
                            <a href="{{ route('gestion.etapes.show', $etape) }}" class="fw-medium">{{ \Illuminate\Support\Str::limit($etape->titre, 40) }}</a>
                          </td>
                          <td>
                            <a href="{{ route('gestion.lots.show', $etape->lot) }}">{{ $etape->lot->code }}</a>
                            <small class="d-block text-body-secondary">{{ $etape->lot->produit->name }}</small>
                          </td>
                          <td>{{ $etape->lieu }}</td>
                          <td>{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}</td>
                          <td class="text-end">
                            <a href="{{ route('gestion.etapes.show', $etape) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Voir">
                              <i class="icon-base bx bx-show"></i>
                            </a>
                            {{-- Modifier et supprimer : auteur de l'étape ou admin --}}
                            @if ($etape->estModifiablePar(auth()->user()))
                              <a href="{{ route('gestion.etapes.edit', $etape) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Modifier">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </a>
                              <button
                                type="button"
                                class="btn btn-sm btn-icon btn-text-danger"
                                title="Supprimer"
                                data-bs-toggle="modal"
                                data-bs-target="#modalSuppression"
                                data-action="{{ route('gestion.etapes.destroy', $etape) }}"
                                data-libelle="l'étape « {{ $etape->titre }} » du lot {{ $etape->lot->code }}">
                                <i class="icon-base bx bx-trash"></i>
                              </button>
                            @endif
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="7" class="text-center py-6">
                            <i class="icon-base bx bx-map-pin icon-lg text-body-secondary d-block mx-auto mb-2"></i>
                            @if ($recherche !== '' || $type || $lotId)
                              Aucune étape ne correspond à votre recherche.
                            @else
                              Aucune étape pour le moment.
                            @endif
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if ($etapes->hasPages())
                  <div class="card-footer pb-0">
                    {{ $etapes->links('pagination::bootstrap-5') }}
                  </div>
                @endif
              </div>

              @include('back.partials.modal-suppression')
@endsection
