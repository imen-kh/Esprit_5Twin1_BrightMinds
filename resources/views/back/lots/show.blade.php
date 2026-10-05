@extends('layouts.back')

@section('title', 'Lot '.$lot->code)

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.lots.index') }}">Lots</a></li>
                  <li class="breadcrumb-item active">{{ $lot->code }}</li>
                </ol>
              </nav>

              <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
                <div>
                  <h4 class="mb-1">
                    Lot {{ $lot->code }}
                    <span class="badge bg-label-{{ $lot->statut_badge }} ms-2">{{ $lot->statut_label }}</span>
                  </h4>
                  <p class="mb-0 text-body-secondary">{{ $lot->produit->name }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  {{-- Page publique de traçabilité (front office), celle que le QR code ouvre --}}
                  <a href="{{ route('tracabilite.show', $lot) }}" class="btn btn-primary" target="_blank" rel="noopener">
                    <i class="icon-base bx bx-link-external me-1"></i> Voir la page publique
                  </a>
                {{-- Modifier et supprimer : propriétaire du produit ou admin --}}
                @if ($peutModifier)
                    <a href="{{ route('gestion.lots.edit', $lot) }}" class="btn btn-outline-primary">
                      <i class="icon-base bx bx-edit-alt me-1"></i> Modifier
                    </a>
                    <button
                      type="button"
                      class="btn btn-outline-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#modalSuppression"
                      data-action="{{ route('gestion.lots.destroy', $lot) }}"
                      data-libelle="le lot {{ $lot->code }} et ses {{ $lot->etapes->count() }} étape(s)">
                      <i class="icon-base bx bx-trash me-1"></i> Supprimer
                    </button>
                @endif
                </div>
              </div>

              @if ($lot->statut === 'rappele')
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                  <i class="icon-base bx bx-error-circle icon-md me-2"></i>
                  <div>Ce lot fait l'objet d'un rappel : il ne doit plus être commercialisé.</div>
                </div>
              @endif

              <div class="row g-6">
                {{-- ===== Colonne gauche : informations du lot, produit, lots liés ===== --}}
                <div class="col-lg-5">
                  <div class="card mb-6">
                    <h5 class="card-header">Informations du lot</h5>
                    <div class="card-body">
                      <dl class="row mb-0">
                        <dt class="col-5 text-body-secondary fw-normal">Code</dt>
                        <dd class="col-7 fw-medium">{{ $lot->code }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Date de production</dt>
                        <dd class="col-7">{{ $lot->date_production->format('d/m/Y') }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Date de péremption</dt>
                        <dd class="col-7">{{ $lot->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Quantité</dt>
                        <dd class="col-7">{{ number_format($lot->quantite, 0, ',', ' ') }} {{ $lot->unite }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Statut</dt>
                        <dd class="col-7"><span class="badge bg-label-{{ $lot->statut_badge }}">{{ $lot->statut_label }}</span></dd>

                        <dt class="col-5 text-body-secondary fw-normal">Notes</dt>
                        <dd class="col-7 mb-0">{{ $lot->notes ?: 'Aucune' }}</dd>
                      </dl>
                    </div>
                  </div>

                  <div class="card mb-6">
                    <h5 class="card-header">Produit</h5>
                    <div class="card-body">
                      <dl class="row mb-0">
                        <dt class="col-5 text-body-secondary fw-normal">Nom</dt>
                        <dd class="col-7 fw-medium">{{ $lot->produit->name }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Catégorie</dt>
                        <dd class="col-7">{{ $lot->produit->category?->name ?? 'Non renseignée' }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Origine</dt>
                        <dd class="col-7">{{ $lot->produit->origin ?: 'Non renseignée' }}</dd>

                        <dt class="col-5 text-body-secondary fw-normal">Propriétaire</dt>
                        <dd class="col-7 mb-0">
                          {{ $lot->produit->user?->nom_entreprise ?: $lot->produit->user?->name }}
                          @if ($lot->produit->user?->type_acteur)
                            <span class="badge bg-label-secondary ms-1">{{ ucfirst($lot->produit->user->type_acteur) }}</span>
                          @endif
                        </dd>
                      </dl>
                    </div>
                  </div>

                  <div class="card mb-6">
                    <h5 class="card-header">QR code du lot</h5>
                    <div class="card-body text-center">
                      {{-- À imprimer sur l'emballage : il ouvre la page publique de traçabilité --}}
                      <div class="d-inline-block bg-white border rounded p-3 mb-4">{!! preg_replace('/^<\?xml.*?\?>\s*/s', '', $qrCode) !!}</div>
                      <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('tracabilite.show', $lot) }}" class="btn btn-sm btn-primary" target="_blank" rel="noopener">
                          <i class="icon-base bx bx-link-external icon-sm me-1"></i> Voir la page publique
                        </a>
                        <a href="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}" download="qr-code-{{ $lot->code }}.svg" class="btn btn-sm btn-outline-secondary">
                          <i class="icon-base bx bx-download icon-sm me-1"></i> Télécharger
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="card">
                    <h5 class="card-header">Chaîne des lots</h5>
                    <div class="card-body">
                      <h6 class="mb-2">Lot d'origine (matière première)</h6>
                      @if ($lot->lotOrigine)
                        <p class="mb-4">
                          <i class="icon-base bx bx-up-arrow-alt text-body-secondary me-1"></i>
                          {{-- Lien seulement si l'utilisateur a le droit de consulter ce lot --}}
                          @if ($lot->lotOrigine->estVisiblePar(auth()->user()))
                            <a href="{{ route('gestion.lots.show', $lot->lotOrigine) }}" class="fw-medium">{{ $lot->lotOrigine->code }}</a>
                          @else
                            <span class="fw-medium">{{ $lot->lotOrigine->code }}</span>
                          @endif
                          — {{ $lot->lotOrigine->produit->name }}
                        </p>
                      @else
                        <p class="mb-4 text-body-secondary">Aucun : ce lot n'est pas issu d'un autre lot.</p>
                      @endif

                      <h6 class="mb-2">Lots dérivés</h6>
                      @forelse ($lot->lotsDerives as $derive)
                        <p class="mb-1">
                          <i class="icon-base bx bx-down-arrow-alt text-body-secondary me-1"></i>
                          @if ($derive->estVisiblePar(auth()->user()))
                            <a href="{{ route('gestion.lots.show', $derive) }}" class="fw-medium">{{ $derive->code }}</a>
                          @else
                            <span class="fw-medium">{{ $derive->code }}</span>
                          @endif
                          — {{ $derive->produit->name }}
                        </p>
                      @empty
                        <p class="mb-0 text-body-secondary">Aucun lot n'a été fabriqué à partir de celui-ci.</p>
                      @endforelse
                    </div>
                  </div>
                </div>

                {{-- ===== Colonne droite : timeline des étapes (relation 1-N) ===== --}}
                <div class="col-lg-7">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">
                        Étapes de traçabilité
                        <span class="badge rounded-pill bg-label-secondary ms-1">{{ $lot->etapes->count() }}</span>
                      </h5>
                      @if ($peutAjouterEtape)
                        <a href="{{ route('gestion.etapes.create', ['lot_id' => $lot->id]) }}" class="btn btn-sm btn-primary">
                          <i class="icon-base bx bx-plus me-1"></i> Ajouter une étape
                        </a>
                      @endif
                    </div>
                    <div class="card-body">
                      @if ($lot->etapes->isEmpty())
                        <div class="text-center py-6">
                          <i class="icon-base bx bx-map-pin icon-lg text-body-secondary d-block mx-auto mb-2"></i>
                          Aucune étape pour le moment.
                        </div>
                      @else
                        <ul class="timeline-etapes">
                          @foreach ($lot->etapes as $etape)
                            <li class="timeline-item">
                              <span class="timeline-point bg-label-{{ $etape->type['couleur'] }}">
                                <i class="icon-base bx {{ $etape->type['icone'] }}"></i>
                              </span>
                              <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                  <span class="badge bg-label-{{ $etape->type['couleur'] }} mb-1">{{ $etape->type['label'] }}</span>
                                  <h6 class="mb-0">
                                    <a href="{{ route('gestion.etapes.show', $etape) }}" class="text-heading">{{ $etape->titre }}</a>
                                  </h6>
                                </div>
                                <small class="text-body-secondary">{{ $etape->date_etape->format('d/m/Y à H:i') }}</small>
                              </div>
                              <p class="mb-1">
                                <i class="icon-base bx bx-map icon-sm text-body-secondary me-1"></i>{{ $etape->lieu }}
                                <span class="mx-1 text-body-secondary">·</span>
                                <i class="icon-base bx bx-user icon-sm text-body-secondary me-1"></i>{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}
                                @if ($etape->temperature_formatee)
                                  <span class="mx-1 text-body-secondary">·</span>
                                  <i class="icon-base bx bx-thermometer icon-sm text-body-secondary me-1"></i>{{ $etape->temperature_formatee }}
                                @endif
                              </p>
                              @if ($etape->description)
                                <p class="mb-1 text-body-secondary">{{ $etape->description }}</p>
                              @endif
                              {{-- Modifier et supprimer : auteur de l'étape ou admin --}}
                              @if ($etape->estModifiablePar(auth()->user()))
                                <div class="mt-2">
                                  <a href="{{ route('gestion.etapes.edit', $etape) }}" class="btn btn-sm btn-text-secondary">
                                    <i class="icon-base bx bx-edit-alt icon-sm me-1"></i> Modifier
                                  </a>
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-text-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalSuppression"
                                    data-action="{{ route('gestion.etapes.destroy', $etape) }}"
                                    data-libelle="l'étape « {{ $etape->titre }} »">
                                    <i class="icon-base bx bx-trash icon-sm me-1"></i> Supprimer
                                  </button>
                                </div>
                              @endif
                            </li>
                          @endforeach
                        </ul>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              @include('back.partials.modal-suppression')
              @include('back.partials.timeline-styles')
@endsection
