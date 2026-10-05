@extends('layouts.back')

@section('title', 'Tableau de bord')

@section('content')
              {{-- ===== Bienvenue ===== --}}
              <div class="card mb-6">
                <div class="d-flex align-items-center row">
                  <div class="col-sm-8">
                    <div class="card-body">
                      <h5 class="card-title text-primary mb-3">Bienvenue, {{ auth()->user()->name }} ! 🎉</h5>
                      <p class="mb-6">
                        NutriTrace suit aujourd'hui <strong>{{ $totaux['lots'] }} lot(s)</strong> et
                        <strong>{{ $totaux['etapes'] }} étape(s)</strong> de traçabilité, de la ferme à l'assiette.
                      </p>
                      <a href="{{ route('gestion.lots.index') }}" class="btn btn-sm btn-outline-primary">Voir les lots</a>
                    </div>
                  </div>
                  <div class="col-sm-4 text-center text-sm-left">
                    <div class="card-body pb-0 px-0 px-md-6">
                      <img
                        src="{{ asset('back/assets/img/illustrations/man-with-laptop.png') }}"
                        height="160"
                        alt="Illustration du tableau de bord" />
                    </div>
                  </div>
                </div>
              </div>

              {{-- ===== Chiffres clés ===== --}}
              <div class="row g-6 mb-6">
                @foreach ([
                    ['Utilisateurs', $totaux['utilisateurs'], 'bx-group', 'primary', null],
                    ['Produits', $totaux['produits'], 'bx-package', 'success', route('admin.produits.index')],
                    ['Catégories', $totaux['categories'], 'bx-category', 'info', route('admin.categories.index')],
                    ['Lots', $totaux['lots'], 'bx-git-branch', 'warning', route('gestion.lots.index')],
                    ['Étapes', $totaux['etapes'], 'bx-map-pin', 'secondary', route('gestion.etapes.index')],
                    ['Lots rappelés', $lotsRappeles->count(), 'bx-error-circle', 'danger', route('gestion.lots.index', ['statut' => 'rappele'])],
                ] as [$libelle, $valeur, $icone, $couleur, $lien])
                  <div class="col-xl-2 col-md-4 col-6">
                    <div class="card h-100">
                      <div class="card-body">
                        <div class="avatar flex-shrink-0 mb-4">
                          <span class="avatar-initial rounded bg-label-{{ $couleur }}">
                            <i class="icon-base bx {{ $icone }} icon-md"></i>
                          </span>
                        </div>
                        <p class="mb-1">{{ $libelle }}</p>
                        <h4 class="card-title mb-2">{{ $valeur }}</h4>
                        @if ($lien)
                          <a href="{{ $lien }}" class="small">Voir le détail</a>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>

              <div class="row g-6 mb-6">
                {{-- ===== Lots par statut ===== --}}
                <div class="col-lg-4">
                  <div class="card h-100">
                    <h5 class="card-header">Lots par statut</h5>
                    <div class="card-body">
                      @foreach ($lotsParStatut as $cle => $statut)
                        <div class="mb-4">
                          <div class="d-flex justify-content-between mb-1">
                            <a href="{{ route('gestion.lots.index', ['statut' => $cle]) }}" class="text-heading">{{ $statut['label'] }}</a>
                            <span class="fw-medium">{{ $statut['total'] }}</span>
                          </div>
                          <div class="progress" style="height: 8px;">
                            <div
                              class="progress-bar bg-{{ $statut['badge'] }}"
                              role="progressbar"
                              style="width: {{ $totaux['lots'] > 0 ? round($statut['total'] * 100 / $totaux['lots']) : 0 }}%"></div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>

                {{-- ===== Étapes par type ===== --}}
                <div class="col-lg-4">
                  <div class="card h-100">
                    <h5 class="card-header">Étapes par type</h5>
                    <div class="card-body">
                      <ul class="p-0 m-0">
                        @foreach ($etapesParType as $cle => $type)
                          <li class="d-flex align-items-center mb-4">
                            <div class="avatar flex-shrink-0 me-3">
                              <span class="avatar-initial rounded bg-label-{{ $type['couleur'] }}">
                                <i class="icon-base bx {{ $type['icone'] }}"></i>
                              </span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between">
                              <a href="{{ route('gestion.etapes.index', ['type_etape' => $cle]) }}" class="text-heading">{{ $type['label'] }}</a>
                              <span class="fw-medium">{{ $type['total'] }}</span>
                            </div>
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                </div>

                {{-- ===== Utilisateurs ===== --}}
                <div class="col-lg-4">
                  <div class="card h-100">
                    <h5 class="card-header">Utilisateurs</h5>
                    <div class="card-body">
                      <ul class="p-0 m-0">
                        @foreach (['admin' => 'Administrateurs', 'acteur' => 'Acteurs de la chaîne', 'consommateur' => 'Consommateurs'] as $role => $libelle)
                          <li class="d-flex justify-content-between mb-3">
                            <span>{{ $libelle }}</span>
                            <span class="fw-medium">{{ $parRole[$role] ?? 0 }}</span>
                          </li>
                        @endforeach
                      </ul>
                      <hr />
                      <p class="mb-3 text-body-secondary">Acteurs par métier</p>
                      @foreach (['producteur' => 'Producteurs', 'transformateur' => 'Transformateurs', 'distributeur' => 'Distributeurs'] as $type => $libelle)
                        <span class="badge bg-label-primary me-1 mb-1">{{ $libelle }} : {{ $parTypeActeur[$type] ?? 0 }}</span>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>

              <div class="row g-6">
                {{-- ===== Lots rappelés ===== --}}
                <div class="col-lg-5">
                  <div class="card h-100">
                    <h5 class="card-header">Lots rappelés</h5>
                    <div class="card-body">
                      @forelse ($lotsRappeles as $lot)
                        <div class="d-flex align-items-center mb-4">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-danger"><i class="icon-base bx bx-error-circle"></i></span>
                          </div>
                          <div>
                            <a href="{{ route('gestion.lots.show', $lot) }}" class="fw-medium">{{ $lot->code }}</a>
                            <small class="d-block text-body-secondary">{{ $lot->produit->name }} · produit le {{ $lot->date_production->format('d/m/Y') }}</small>
                          </div>
                        </div>
                      @empty
                        <p class="mb-0 text-body-secondary">Aucun lot n'est rappelé actuellement.</p>
                      @endforelse
                    </div>
                  </div>
                </div>

                {{-- ===== Dernières étapes enregistrées ===== --}}
                <div class="col-lg-7">
                  <div class="card h-100">
                    <h5 class="card-header">Dernières étapes enregistrées</h5>
                    <div class="table-responsive text-nowrap">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Lot</th>
                            <th>Acteur</th>
                          </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                          @forelse ($dernieresEtapes as $etape)
                            <tr>
                              <td>{{ $etape->date_etape->format('d/m/Y H:i') }}</td>
                              <td><span class="badge bg-label-{{ $etape->type['couleur'] }}">{{ $etape->type['label'] }}</span></td>
                              <td>
                                <a href="{{ route('gestion.lots.show', $etape->lot) }}">{{ $etape->lot->code }}</a>
                                <small class="d-block text-body-secondary">{{ $etape->lot->produit->name }}</small>
                              </td>
                              <td>{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}</td>
                            </tr>
                          @empty
                            <tr><td colspan="4" class="text-center py-6">Aucune étape pour le moment.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
@endsection
