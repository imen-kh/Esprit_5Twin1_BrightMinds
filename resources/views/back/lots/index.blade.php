@extends('layouts.back')

@section('title', 'Lots')

@section('content')
              @if (session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                  {{ session('error') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
              @endif

              <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
                <div>
                  <h4 class="mb-1">Lots</h4>
                  <p class="mb-0 text-body-secondary">Suivez vos lots de production, de la ferme à l'assiette.</p>
                </div>
                @if ($peutCreer)
                  <a href="{{ route('gestion.lots.create') }}" class="btn btn-primary">
                    <i class="icon-base bx bx-plus me-1"></i> Nouveau lot
                  </a>
                @endif
              </div>

              <div class="card">
                {{-- Recherche et filtre : formulaire GET, les critères restent dans l'URL et la pagination --}}
                <div class="card-header">
                  @if ($produitFiltre ?? null)
                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                      <span>Lots du produit <strong>{{ $produitFiltre->name }}</strong></span>
                      <a href="{{ route('gestion.lots.index') }}" class="btn btn-sm btn-outline-secondary">Voir tous les lots</a>
                    </div>
                  @endif
                  <form method="GET" action="{{ route('gestion.lots.index') }}" class="row g-4 align-items-end">
                    @if ($produitFiltre ?? null)
                      <input type="hidden" name="produit_id" value="{{ $produitFiltre->id }}" />
                    @endif
                    <div class="col-md-5">
                      <label for="q" class="form-label">Rechercher</label>
                      <input
                        type="search"
                        class="form-control"
                        id="q"
                        name="q"
                        value="{{ $recherche }}"
                        placeholder="Code du lot ou nom du produit" />
                    </div>
                    <div class="col-md-4">
                      <label for="statut" class="form-label">Statut</label>
                      <select class="form-select" id="statut" name="statut">
                        <option value="">Tous les statuts</option>
                        @foreach (\App\Models\Lot::STATUTS as $cle => $infos)
                          <option value="{{ $cle }}" @selected($statut === $cle)>{{ $infos['label'] }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                      <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="icon-base bx bx-search me-1"></i> Filtrer
                      </button>
                      @if ($recherche !== '' || $statut)
                        <a href="{{ route('gestion.lots.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
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
                        <th>Code</th>
                        <th>Produit</th>
                        <th>Production</th>
                        <th>Quantité</th>
                        <th class="text-center">Étapes</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      @forelse ($lots as $lot)
                        <tr>
                          <td>
                            <a href="{{ route('gestion.lots.show', $lot) }}" class="fw-medium">{{ $lot->code }}</a>
                            {{-- Anomalies détectées par les contrôles automatiques (Lot::anomalies) --}}
                            @if ($alertes[$lot->id]['nombre'] ?? 0)
                              <i
                                class="icon-base bx bx-error text-{{ \App\Models\Lot::GRAVITES[$alertes[$lot->id]['gravite']]['badge'] }} ms-1"
                                title="{{ $alertes[$lot->id]['nombre'] }} anomalie(s) détectée(s)"
                                aria-label="{{ $alertes[$lot->id]['nombre'] }} anomalie(s) détectée(s)"></i>
                            @endif
                          </td>
                          <td>{{ $lot->produit->name }}</td>
                          <td>{{ $lot->date_production->format('d/m/Y') }}</td>
                          <td>{{ number_format($lot->quantite, 0, ',', ' ') }} {{ $lot->unite }}</td>
                          <td class="text-center">
                            <span class="badge rounded-pill bg-label-secondary">{{ $lot->etapes_count }}</span>
                          </td>
                          <td>
                            <span class="badge bg-label-{{ $lot->statut_badge }}">{{ $lot->statut_label }}</span>
                            {{-- Péremption dépassée ou proche --}}
                            @if ($alerte = $lot->alertePeremption())
                              <span class="badge bg-{{ $alerte['niveau'] }} ms-1" title="{{ $alerte['message'] }}">{{ $alerte['niveau'] === 'danger' ? 'Périmé' : 'J-'.$lot->joursAvantPeremption() }}</span>
                            @endif
                          </td>
                          <td class="text-end">
                            <a href="{{ route('gestion.lots.show', $lot) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Voir">
                              <i class="icon-base bx bx-show"></i>
                            </a>
                            {{-- Modifier et supprimer : propriétaire du produit ou admin --}}
                            @if ($lot->estModifiablePar(auth()->user()))
                              <a href="{{ route('gestion.lots.edit', $lot) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Modifier">
                                <i class="icon-base bx bx-edit-alt"></i>
                              </a>
                              <button
                                type="button"
                                class="btn btn-sm btn-icon btn-text-danger"
                                title="Supprimer"
                                data-bs-toggle="modal"
                                data-bs-target="#modalSuppression"
                                data-action="{{ route('gestion.lots.destroy', $lot) }}"
                                data-libelle="le lot {{ $lot->code }} et ses {{ $lot->etapes_count }} étape(s)">
                                <i class="icon-base bx bx-trash"></i>
                              </button>
                            @endif
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="7" class="text-center py-6">
                            <i class="icon-base bx bx-package icon-lg text-body-secondary d-block mx-auto mb-2"></i>
                            @if ($recherche !== '' || $statut)
                              Aucun lot ne correspond à votre recherche.
                            @else
                              Aucun lot pour le moment.
                            @endif
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if ($lots->hasPages())
                  <div class="card-footer pb-0">
                    {{ $lots->links('pagination::bootstrap-5') }}
                  </div>
                @endif
              </div>

              @include('back.partials.modal-suppression')
@endsection
