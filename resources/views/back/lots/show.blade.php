@extends('layouts.back')

@section('title', 'Lot '.$lot->code)

@section('content')
              @if (session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                  {{ session('error') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
              @endif

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
                  <a href="{{ route('tracabilite.pdf', $lot) }}" class="btn btn-outline-secondary">
                    <i class="icon-base bx bxs-file-pdf me-1"></i> Fiche PDF
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
                  <div>
                    Ce lot fait l'objet d'un rappel : il ne doit plus être commercialisé.
                    @if ($lot->motif_rappel)
                      <br><strong>Motif :</strong> {{ $lot->motif_rappel }}
                    @endif
                  </div>
                </div>
              @endif

              {{-- Péremption dépassée ou proche --}}
              @if ($alerte = $lot->alertePeremption())
                <div class="alert alert-{{ $alerte['niveau'] }} d-flex align-items-center" role="alert">
                  <i class="icon-base bx bx-time-five icon-md me-2"></i>
                  <div><strong>{{ $alerte['titre'] }} :</strong> {{ $alerte['message'] }}</div>
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
                        <dd class="col-7">{{ $lot->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}@if ($lot->estPerime()) <span class="badge bg-label-danger ms-1">Périmé</span>@endif</dd>

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

                {{-- ===== Colonne droite : analyse IA, score, histoire, puis timeline des étapes (relation 1-N) ===== --}}
                <div class="col-lg-7">
                  {{-- Analyse du lot : contrôles automatiques (toujours affichés) enrichis par l'IA à la demande --}}
                  <div class="card mb-6">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">🔍 Analyse IA du lot</h5>
                      <form method="POST" action="{{ route('gestion.lots.analyser', $lot) }}" class="form-ia">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary" data-chargement="Analyse en cours…">
                          <i class="icon-base bx bx-brain me-1"></i> Analyser
                        </button>
                      </form>
                    </div>
                    <div class="card-body">
                      @if ($analyseIA)
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                          <span class="badge bg-{{ \App\Services\TracabiliteIAService::NIVEAUX_RISQUE[$analyseIA['niveau_risque']] ?? 'secondary' }} fs-6 px-3 py-2">
                            Risque {{ $analyseIA['niveau_risque'] }}
                          </span>
                          <span class="badge bg-label-primary"><i class="icon-base bx bx-bot icon-sm me-1"></i>Généré par IA</span>
                          <small class="text-body-secondary ms-auto">Analysé le {{ $analyseIA['analyse_le'] }}</small>
                        </div>
                        <p class="mb-4">{{ $analyseIA['resume'] }}</p>
                      @elseif (! $iaConfiguree)
                        <p class="text-body-secondary small mb-4">IA non configurée (clé GEMINI_API_KEY absente) : seuls les contrôles automatiques sont affichés.</p>
                      @else
                        <p class="text-body-secondary small mb-4">Cliquez sur « Analyser » pour obtenir le niveau de risque et des recommandations de l'IA.</p>
                      @endif

                      <h6 class="mb-2">
                        Contrôles automatiques
                        <span class="badge rounded-pill bg-label-{{ $anomalies ? 'danger' : 'success' }} ms-1">{{ count($anomalies) }}</span>
                      </h6>
                      @forelse ($anomalies as $anomalie)
                        <div class="d-flex align-items-start gap-2 mb-2">
                          <span class="badge bg-label-{{ \App\Models\Lot::GRAVITES[$anomalie['gravite']]['badge'] }} flex-shrink-0">{{ \App\Models\Lot::GRAVITES[$anomalie['gravite']]['label'] }}</span>
                          <span>{{ $anomalie['message'] }}</span>
                        </div>
                      @empty
                        <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                          <i class="icon-base bx bx-check-shield icon-md me-2"></i>
                          Aucune anomalie : chaîne du froid respectée, trajets cohérents, étapes complètes.
                        </div>
                      @endforelse

                      @if ($analyseIA && $analyseIA['recommandations'])
                        <h6 class="mt-4 mb-2">Recommandations de l'IA</h6>
                        <ul class="mb-0 ps-4">
                          @foreach ($analyseIA['recommandations'] as $recommandation)
                            <li class="mb-1">{{ $recommandation }}</li>
                          @endforeach
                        </ul>
                      @endif
                    </div>
                  </div>

                  {{-- Score de transparence (sans IA) : détail des critères --}}
                  @php($couleurScore = \App\Models\Lot::couleurScore($score))
                  <div class="card mb-6">
                    <h5 class="card-header">Score de transparence</h5>
                    <div class="card-body">
                      <div class="d-flex align-items-center gap-4 mb-4">
                        <div class="text-nowrap">
                          <span class="display-6 fw-bold text-{{ $couleurScore }}">{{ $score }}</span><span class="text-body-secondary">/100</span>
                        </div>
                        <div class="progress flex-grow-1" style="height: 12px;">
                          <div class="progress-bar bg-{{ $couleurScore }}" role="progressbar" style="width: {{ $score }}%" aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                      </div>
                      @foreach ($criteres as $critere)
                        <div class="d-flex justify-content-between small mb-1">
                          <span>
                            <i class="icon-base bx {{ $critere['points'] === $critere['max'] ? 'bx-check text-success' : 'bx-x text-danger' }} icon-sm me-1"></i>{{ $critere['libelle'] }}
                          </span>
                          <span class="fw-medium">{{ $critere['points'] }}/{{ $critere['max'] }}</span>
                        </div>
                      @endforeach
                    </div>
                  </div>

                  {{-- Histoire du produit affichée sur la page publique, en cache sur le lot --}}
                  <div class="card mb-6">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">📖 L'histoire du produit</h5>
                      <form method="POST" action="{{ route('gestion.lots.histoire', $lot) }}" class="form-ia">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" data-chargement="Rédaction en cours…">
                          <i class="icon-base bx bx-refresh me-1"></i> Régénérer l'histoire (IA)
                        </button>
                      </form>
                    </div>
                    <div class="card-body">
                      @if ($lot->histoire_ia)
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                          <span class="badge bg-label-primary"><i class="icon-base bx bx-bot icon-sm me-1"></i>Généré par IA</span>
                          @unless ($histoireAJour)
                            <span class="badge bg-label-warning">Le lot a changé depuis : l'histoire sera régénérée</span>
                          @endunless
                          <small class="text-body-secondary ms-auto">Le {{ $lot->histoire_ia_generee_le?->format('d/m/Y à H:i') }}</small>
                        </div>
                        <p class="mb-0 fst-italic">{{ $lot->histoire_ia }}</p>
                      @else
                        <p class="mb-0 text-body-secondary">Aucune histoire générée pour le moment. Elle est rédigée automatiquement à la première visite de la page publique, ou avec le bouton ci-dessus.</p>
                      @endif
                    </div>
                  </div>

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

@push('scripts')
  <script>
    // Appels IA (quelques secondes) : bouton désactivé avec un indicateur, pour éviter un double envoi
    document.querySelectorAll('.form-ia').forEach(function (formulaire) {
      formulaire.addEventListener('submit', function () {
        const bouton = formulaire.querySelector('button[type="submit"]');
        bouton.disabled = true;
        bouton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + bouton.dataset.chargement;
      });
    });
  </script>
@endpush
