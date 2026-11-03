{{-- Module Réclamations & Avis : section « Avis » de la fiche produit --}}
@php
    $synthese = $avis['synthese'];
    $aConvertir = session('avis_a_convertir') ? \App\Models\Avis::find(session('avis_a_convertir')) : null;
    $aConvertir ??= ($avis['monAvis'] && $avis['monAvis']->meriteReclamation()) ? $avis['monAvis'] : null;
@endphp

<section id="avis" class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="mb-1">Avis clients</h2>
            <p class="text-muted mb-0">Notes et retours des clients sur {{ $product->name }}.</p>
        </div>
        @if ($avis['total'] > 0)
            <form method="GET" action="{{ route('produit.detail', $product) }}#avis" class="d-flex align-items-center gap-2">
                <label for="tri-avis" class="small text-muted text-nowrap">Trier par</label>
                <select id="tri-avis" name="tri" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach (\App\Services\AvisProduitService::TRIS as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected($avis['tri'] === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if (session('success') && request()->routeIs('produit.detail'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Avis négatif décrivant un vrai problème : proposition de réclamation en un clic --}}
    @if ($aConvertir)
        <div class="ra-bandeau-conversion p-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-hands-helping fa-2x text-secondary"></i>
                <div>
                    <h5 class="mb-1">Nous sommes désolés pour cette expérience</h5>
                    <p class="mb-0 text-muted">Votre avis décrit un problème concret. Voulez-vous que notre équipe le traite ? Une réclamation sera ouverte avec votre texte.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('avis.convertir', $aConvertir) }}">
                @csrf
                <button class="btn btn-secondary rounded-pill px-4 text-white"><i class="fas fa-file-medical me-2"></i>Ouvrir une réclamation</button>
            </form>
        </div>
    @endif

    <div class="row g-4">
        {{-- Résumé des notes --}}
        <div class="col-lg-4">
            <div class="ra-carte p-4 h-100">
                @if ($avis['total'] > 0)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="ra-note-geante">{{ str_replace('.', ',', (string) $avis['moyenne']) }}</div>
                        <div>
                            <x-etoiles :note="$avis['moyenne']" taille="1.15rem" />
                            <div class="small text-muted">{{ $avis['total'] }} avis</div>
                        </div>
                    </div>
                    @foreach ($avis['repartition'] as $etoiles => $nombre)
                        <div class="d-flex align-items-center gap-2 mb-2 small">
                            <span class="text-nowrap" style="width: 2.4rem;">{{ $etoiles }} <i class="fas fa-star text-secondary"></i></span>
                            <div class="ra-barre flex-grow-1"><span style="width: {{ $avis['total'] ? round(100 * $nombre / $avis['total']) : 0 }}%"></span></div>
                            <span class="text-muted text-end" style="width: 2rem;">{{ $nombre }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3">
                        <i class="far fa-comment-dots fa-3x text-primary mb-3"></i>
                        <h5>Aucun avis pour le moment</h5>
                        <p class="text-muted mb-0">Soyez le premier à partager votre expérience.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Synthèse IA : ce que les clients en pensent --}}
        <div class="col-lg-8">
            @if ($synthese)
                <div class="ra-carte-ia p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                        <h5 class="mb-0">Ce que les clients en pensent</h5>
                        <span class="ra-badge-ia"><i class="fas fa-robot"></i>{{ $synthese['source'] === 'ia' ? 'Résumé par l\'IA DeepSeek' : 'Résumé automatique' }}</span>
                    </div>
                    <p class="mb-3">{{ $synthese['resume'] }}</p>
                    <div class="mb-2">
                        @foreach ($synthese['points_forts'] as $point)
                            <span class="ra-puce-positive"><i class="fas fa-thumbs-up"></i>{{ $point }}</span>
                        @endforeach
                        @foreach ($synthese['points_faibles'] as $point)
                            <span class="ra-puce-negative"><i class="fas fa-exclamation-circle"></i>{{ $point }}</span>
                        @endforeach
                    </div>
                    @if ($synthese['aspects'])
                        <div class="row g-3 mt-1">
                            @foreach ($synthese['aspects'] as $aspect => $valeur)
                                <div class="col-sm-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><i class="fas {{ \App\Models\Avis::ASPECTS[$aspect]['icone'] }} text-primary me-1"></i>{{ \App\Models\Avis::ASPECTS[$aspect]['label'] }}</span>
                                        <strong>{{ str_replace('.', ',', (string) $valeur) }}/5</strong>
                                    </div>
                                    <div class="ra-barre {{ $valeur >= 4 ? 'verte' : ($valeur < 3 ? 'rouge' : '') }}"><span style="width: {{ $valeur * 20 }}%"></span></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="ra-carte p-4 h-100 d-flex align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <i class="fas fa-robot fa-2x text-muted"></i>
                        <p class="mb-0 text-muted">Dès deux avis, l'IA résume ici les points forts et les points faibles du produit (goût, fraîcheur, emballage, prix, livraison).</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Formulaire --}}
    <div class="row mt-4">
        <div class="col-12">
            @auth
                @if ($avis['monAvis'])
                    <div class="ra-carte p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>
                            <i class="fas fa-check-circle text-primary me-2"></i>Vous avez noté ce produit
                            <x-etoiles :note="$avis['monAvis']->note" class="ms-1" />
                            @if ($avis['monAvis']->statut !== 'publie')
                                <span class="badge {{ $avis['monAvis']->statutBadge() }} ms-2">{{ $avis['monAvis']->statutLibelle() }}</span>
                            @endif
                        </span>
                        <a href="{{ route('avis.edit', $avis['monAvis']) }}" class="btn btn-sm btn-outline-primary rounded-pill">Modifier mon avis</a>
                    </div>
                @elseif ($avis['peutPublier'])
                    <form method="POST" action="{{ route('avis.store', $product) }}" class="ra-carte p-4">
                        @csrf
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h4 class="mb-0">Donner mon avis</h4>
                            @if ($avis['achatVerifie'])
                                <span class="ra-verifie"><i class="fas fa-shield-alt me-1"></i>Votre avis portera le badge « Achat vérifié »</span>
                            @endif
                        </div>
                        @include('front.avis._champs', ['avisCourant' => null])
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <small class="text-muted"><i class="fas fa-robot me-1"></i>Votre avis est relu automatiquement (respect, pas de données personnelles) avant publication.</small>
                            <button type="submit" class="btn btn-primary text-white rounded-pill px-4">Publier mon avis</button>
                        </div>
                    </form>
                @endif
            @else
                <div class="ra-carte p-3 text-center">
                    <a href="{{ route('login') }}">Connectez-vous</a> pour donner votre avis sur ce produit.
                </div>
            @endauth
        </div>
    </div>

    {{-- Liste des avis --}}
    <div class="d-flex flex-column gap-3 mt-4">
        @foreach ($avis['liste'] as $unAvis)
            @php $reactions = $avis['mesReactions'][$unAvis->id] ?? collect(); @endphp
            <article class="ra-avis" id="avis-{{ $unAvis->id }}">
                <div class="d-flex gap-3">
                    <span class="ra-avatar" aria-hidden="true">{{ $unAvis->initiales() }}</span>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <strong>{{ $unAvis->user?->name ?? 'Client' }}</strong>
                                @if ($unAvis->achat_verifie)
                                    <span class="ra-verifie ms-1"><i class="fas fa-check me-1"></i>Achat vérifié</span>
                                @endif
                                <div class="small text-muted">{{ $unAvis->created_at?->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
                            </div>
                            <x-etoiles :note="$unAvis->note" />
                        </div>
                        @if ($unAvis->titre)
                            <h6 class="mt-2 mb-1">{{ $unAvis->titre }}</h6>
                        @endif
                        <p class="mb-2 mt-1">{{ $unAvis->commentaire }}</p>
                        @if ($unAvis->aspects)
                            <div class="mb-2">
                                @foreach ($unAvis->aspects as $aspect => $valeur)
                                    @if (isset(\App\Models\Avis::ASPECTS[$aspect]))
                                        <span class="ra-aspect">{{ \App\Models\Avis::ASPECTS[$aspect]['label'] }} {{ $valeur }}/5</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @if ($unAvis->reponse_producteur)
                            <div class="ra-reponse-producteur mt-2">
                                <div class="small fw-bold mb-1"><i class="fas fa-reply me-1 text-primary"></i>Réponse de {{ $product->user->name }}</div>
                                <div class="small">{{ $unAvis->reponse_producteur }}</div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 small">
                            @auth
                                @can('reagir', $unAvis)
                                    <form method="POST" action="{{ route('avis.reagir', $unAvis) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="utile">
                                        <button class="btn btn-sm rounded-pill {{ $reactions->contains('utile') ? 'btn-primary text-white' : 'btn-outline-secondary' }}">
                                            <i class="fas fa-thumbs-up me-1"></i>Utile{{ $unAvis->utile_count ? ' · '.$unAvis->utile_count : '' }}
                                        </button>
                                    </form>
                                    @unless ($reactions->contains('signalement'))
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" type="button">
                                                <i class="far fa-flag me-1"></i>Signaler
                                            </button>
                                            <div class="dropdown-menu">
                                                @foreach (\App\Models\AvisReaction::MOTIFS_SIGNALEMENT as $motif => $libelle)
                                                    <form method="POST" action="{{ route('avis.reagir', $unAvis) }}">
                                                        @csrf
                                                        <input type="hidden" name="type" value="signalement">
                                                        <input type="hidden" name="motif" value="{{ $motif }}">
                                                        <button class="dropdown-item small">{{ $libelle }}</button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted"><i class="fas fa-flag me-1"></i>Signalé</span>
                                    @endunless
                                @else
                                    @if ($unAvis->utile_count)
                                        <span class="text-muted"><i class="fas fa-thumbs-up me-1"></i>{{ $unAvis->utile_count }} personne(s) ont trouvé cet avis utile</span>
                                    @endif
                                @endcan
                                @can('update', $unAvis)
                                    <a href="{{ route('avis.edit', $unAvis) }}" class="btn btn-sm btn-link text-decoration-none">Modifier</a>
                                @endcan
                            @endauth
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if ($avis['liste']->hasPages())
        <div class="mt-4">{{ $avis['liste']->links() }}</div>
    @endif
</section>
