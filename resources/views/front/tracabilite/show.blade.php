@extends('layouts.front')

@section('title', 'Traçabilité du lot '.$lot->code)

@section('content')
        @include('partials.front.page-header', ['titre' => 'Traçabilité du lot'])

        <!-- Traçabilité du lot Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">

                {{-- ===== Alerte de rappel, très visible ===== --}}
                @if ($lot->statut === 'rappele')
                    <div class="alert alert-danger tracabilite-rappel d-flex align-items-center mb-5" role="alert">
                        <i class="fas fa-exclamation-triangle fa-3x me-4"></i>
                        <div>
                            <h3 class="text-white mb-1">Lot rappelé : ne consommez pas ce produit</h3>
                            <p class="mb-0">
                                Le lot {{ $lot->code }} fait l'objet d'un rappel. Rapportez-le au point de vente.
                                @if ($lot->motif_rappel || $lot->notes)
                                    <br><strong>Motif :</strong> {{ $lot->motif_rappel ?: $lot->notes }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- ===== Péremption dépassée ou proche (Lot::alertePeremption) ===== --}}
                @if ($alertePeremption)
                    <div class="alert tracabilite-alerte tracabilite-alerte-{{ $alertePeremption['niveau'] }} d-flex align-items-center mb-5" role="alert">
                        <i class="fas {{ $alertePeremption['niveau'] === 'danger' ? 'fa-ban' : 'fa-hourglass-end' }} fa-3x me-4"></i>
                        <div>
                            <h3 class="text-white mb-1">{{ $alertePeremption['titre'] }}</h3>
                            <p class="mb-0">{{ $alertePeremption['message'] }}</p>
                        </div>
                    </div>
                @endif

                {{-- ===== Rupture de la chaîne du froid, détectée automatiquement (Lot::anomalies) ===== --}}
                @if ($alertesFroid)
                    <div class="alert alert-danger tracabilite-rappel d-flex align-items-center mb-5" role="alert">
                        <i class="fas fa-temperature-high fa-3x me-4"></i>
                        <div>
                            <h3 class="text-white mb-1">Rupture de la chaîne du froid</h3>
                            @foreach ($alertesFroid as $alerte)
                                <p class="mb-0">{{ $alerte['message'] }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="row g-5">
                    {{-- ================= Colonne gauche : produit, lot, QR code ================= --}}
                    <div class="col-lg-4">
                        {{-- Produit --}}
                        <div class="bg-light rounded overflow-hidden mb-4">
                            <img src="{{ $image }}" alt="{{ $lot->produit->name }}" class="w-100 tracabilite-image">
                            <div class="p-4">
                                <span class="badge bg-secondary rounded-pill px-3 py-2 mb-2">{{ $lot->produit->category?->name ?? 'Produit' }}</span>
                                <h3 class="mb-2">{{ $lot->produit->name }}</h3>
                                <p class="mb-2">{{ $lot->produit->description }}</p>
                                <p class="mb-1">
                                    <i class="fas fa-user-tie text-primary me-2"></i>
                                    {{ $lot->produit->user?->nom_entreprise ?: $lot->produit->user?->name }}
                                    @if ($lot->produit->user?->type_acteur)
                                        <small class="text-muted">({{ $lot->produit->user->type_acteur }})</small>
                                    @endif
                                </p>
                                @if ($lot->produit->origin)
                                    <p class="mb-0"><i class="fas fa-map-marker-alt text-primary me-2"></i>Origine : {{ $lot->produit->origin }}</p>
                                @endif
                                {{-- La fiche produit du Catalogue demande un compte : bouton réservé aux visiteurs connectés --}}
                                @auth
                                    <a href="{{ route('produit.detail', $lot->produit) }}" class="btn border-secondary rounded-pill px-4 py-2 text-primary mt-3">
                                        Voir la fiche produit
                                    </a>
                                @endauth
                            </div>
                        </div>

                        {{-- Lot --}}
                        <div class="bg-light rounded p-4 mb-4">
                            <h4 class="text-primary mb-3">Le lot</h4>
                            <table class="table table-borderless mb-0 tracabilite-infos">
                                <tr><th>Code</th><td class="fw-bold">{{ $lot->code }}</td></tr>
                                <tr><th>Statut</th><td><span class="badge bg-{{ $lot->statut_badge }} rounded-pill px-3 py-2">{{ $lot->statut_label }}</span></td></tr>
                                <tr><th>Production</th><td>{{ $lot->date_production->format('d/m/Y') }}</td></tr>
                                <tr><th>Péremption</th><td>{{ $lot->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}@if ($lot->estPerime()) <span class="badge bg-danger ms-1">Périmé</span>@endif</td></tr>
                                <tr><th>Quantité</th><td>{{ number_format($lot->quantite, 0, ',', ' ') }} {{ $lot->unite }}</td></tr>
                            </table>
                        </div>

                        {{-- QR code pointant vers cette page --}}
                        <div class="bg-light rounded p-4 text-center">
                            <h4 class="text-primary mb-3">QR code du lot</h4>
                            {{-- SVG généré par le serveur (simple-qrcode), sans son prologue XML pour l'affichage dans la page --}}
                            <div class="d-inline-block bg-white rounded p-3 mb-3">{!! preg_replace('/^<\?xml.*?\?>\s*/s', '', $qrCode) !!}</div>
                            <p class="mb-3">Scannez ce code pour retrouver cette page.</p>
                            <a
                                href="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}"
                                download="qr-code-{{ $lot->code }}.svg"
                                class="btn btn-primary rounded-pill text-white px-4 py-2">
                                <i class="fas fa-download me-2"></i>Télécharger le QR code
                            </a>
                            <a href="{{ route('tracabilite.pdf', $lot) }}" class="btn border-secondary rounded-pill text-primary px-4 py-2 mt-2">
                                <i class="fas fa-file-pdf me-2"></i>Fiche de traçabilité (PDF)
                            </a>
                        </div>
                    </div>

                    {{-- ================= Colonne droite : chiffres, chaîne, carte ================= --}}
                    <div class="col-lg-8">
                        {{-- Score de transparence : jauge circulaire colorée --}}
                        @php($couleurScore = ['success' => '#198754', 'warning' => '#ffb524', 'danger' => '#dc3545'][\App\Models\Lot::couleurScore($score)])
                        <div class="bg-light rounded p-4 mb-4 d-flex flex-wrap align-items-center gap-4">
                            <div class="tracabilite-jauge" style="--score: {{ $score }}; --couleur: {{ $couleurScore }};" role="img" aria-label="Score de transparence : {{ $score }} sur 100">
                                <span><span><strong>{{ $score }}</strong><small>/100</small></span></span>
                            </div>
                            <div class="flex-grow-1 tracabilite-jauge-texte">
                                <h4 class="text-primary mb-2">Score de transparence</h4>
                                <p class="mb-0">
                                    Il mesure la complétude et la cohérence des informations de ce lot : chaîne complète de la
                                    production à la distribution, coordonnées GPS, températures, matière première connue et
                                    absence d'anomalie détectée.
                                </p>
                            </div>
                        </div>

                        {{-- Statistiques de la chaîne complète --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <div class="bg-light rounded p-4 text-center h-100">
                                    <i class="fas fa-route fa-2x text-primary mb-2"></i>
                                    <h2 class="mb-0">{{ $stats['nombre_etapes'] }}</h2>
                                    <p class="mb-0">étape(s) enregistrée(s)</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded p-4 text-center h-100">
                                    <i class="fas fa-hourglass-half fa-2x text-primary mb-2"></i>
                                    <h2 class="mb-0">{{ $stats['duree_jours'] }}</h2>
                                    <p class="mb-0">jour(s) de la première à la dernière étape</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded p-4 text-center h-100">
                                    <i class="fas fa-road fa-2x text-primary mb-2"></i>
                                    <h2 class="mb-0">{{ number_format($stats['distance_km'], 0, ',', ' ') }} km</h2>
                                    <p class="mb-0">parcourus, à vol d'oiseau</p>
                                </div>
                            </div>
                        </div>

                        {{-- Histoire du produit rédigée par IA à partir de la chaîne (TracabiliteIAService::histoire) --}}
                        @if ($histoire)
                            <div class="bg-light rounded p-4 mb-4 tracabilite-histoire">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <h4 class="text-primary mb-0">📖 L'histoire de votre produit</h4>
                                    <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-robot me-1"></i>Généré par IA</span>
                                </div>
                                <p class="mb-2 fs-5 fst-italic">{{ $histoire }}</p>
                                <small class="text-muted">Récit rédigé automatiquement à partir des étapes enregistrées ci-dessous.</small>
                            </div>
                        @endif

                        {{-- Assistant traçabilité : questions en langage naturel, réponses tirées des seules données du lot --}}
                        @if ($iaConfiguree && $chaine->isNotEmpty())
                            <div class="bg-light rounded p-4 mb-4" id="assistant" data-url="{{ route('tracabilite.assistant', $lot) }}" data-token="{{ csrf_token() }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <h4 class="text-primary mb-0">💬 Posez une question sur ce produit</h4>
                                    <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-robot me-1"></i>Assistant IA</span>
                                </div>

                                <div class="assistant-fil mb-3" id="assistant-fil" aria-live="polite">
                                    <div class="assistant-bulle assistant-bulle-ia">
                                        Bonjour ! Je réponds uniquement à partir des informations enregistrées pour le lot {{ $lot->code }}.
                                    </div>
                                </div>

                                {{-- Suggestions : un clic pose la question --}}
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @foreach (array_filter([
                                        'Ce produit a-t-il respecté la chaîne du froid ?',
                                        $lot->lotOrigine ? 'D\'où vient la matière première ?' : 'D\'où vient ce produit ?',
                                        'Combien de temps a duré son parcours ?',
                                        'Quand expire-t-il ?',
                                    ]) as $suggestion)
                                        <button type="button" class="btn btn-sm border-secondary rounded-pill text-primary assistant-suggestion">{{ $suggestion }}</button>
                                    @endforeach
                                </div>

                                <form id="assistant-formulaire" class="d-flex gap-2">
                                    <input type="text" id="assistant-question" class="form-control rounded-pill px-4" maxlength="300" required
                                        placeholder="Ex. : D'où viennent les fraises ?" aria-label="Votre question">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 text-white" id="assistant-envoyer">
                                        <i class="fas fa-paper-plane"></i><span class="d-none d-md-inline ms-2">Envoyer</span>
                                    </button>
                                </form>
                                <small class="text-muted d-block mt-2">Réponses générées par IA à partir des données du lot ; 10 questions par minute au maximum.</small>
                            </div>
                        @endif

                        {{-- Chaîne complète : « Matière première » puis « Produit transformé » si le lot a une origine --}}
                        @if ($chaine->isEmpty())
                            <div class="bg-light rounded p-5 text-center mb-4">
                                <i class="fas fa-route fa-2x text-muted mb-3"></i>
                                <p class="mb-0">Aucune étape n'a encore été enregistrée pour ce lot.</p>
                            </div>
                        @endif

                        @foreach ($segments as $segment)
                            @continue($segment['etapes']->isEmpty() && $segments->count() === 1)

                            <div class="bg-light rounded p-4 mb-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                    <h4 class="text-primary mb-0">
                                        @if ($segments->count() > 1)
                                            <i class="fas {{ $segment['lot']->is($lot) ? 'fa-industry' : 'fa-seedling' }} me-2"></i>
                                        @endif
                                        {{ $segment['titre'] }}
                                    </h4>
                                    <span>
                                        {{ $segment['lot']->produit->name }}
                                        @if ($segment['lot']->is($lot))
                                            <span class="badge bg-primary rounded-pill px-3 py-2 ms-1">{{ $segment['lot']->code }}</span>
                                        @else
                                            <a href="{{ route('tracabilite.show', $segment['lot']) }}" class="badge bg-secondary rounded-pill px-3 py-2 ms-1">{{ $segment['lot']->code }}</a>
                                        @endif
                                    </span>
                                </div>

                                @if ($segment['etapes']->isEmpty())
                                    <p class="mb-0 text-muted">Aucune étape enregistrée pour ce lot.</p>
                                @else
                                    {{-- Timeline verticale : une icône et une couleur par type d'étape --}}
                                    <ul class="tracabilite-timeline">
                                        @foreach ($segment['etapes'] as $etape)
                                            <li>
                                                <span class="tracabilite-point bg-{{ $etape->type['couleur'] }}">
                                                    <i class="fas {{ \App\Models\EtapeTracabilite::ICONES_FRONT[$etape->type_etape] ?? 'fa-map-marker-alt' }}"></i>
                                                </span>
                                                <div class="bg-white rounded p-3">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                                        <span class="badge bg-{{ $etape->type['couleur'] }} rounded-pill px-3 py-2">{{ $etape->type['label'] }}</span>
                                                        <small class="text-muted"><i class="fas fa-clock me-1"></i>{{ $etape->date_etape->format('d/m/Y à H:i') }}</small>
                                                    </div>
                                                    <h5 class="mb-2">{{ $etape->titre }}</h5>
                                                    <p class="mb-0">
                                                        <span class="me-3"><i class="fas fa-map-marker-alt text-primary me-1"></i>{{ $etape->lieu }}</span>
                                                        <span class="me-3"><i class="fas fa-user-tie text-primary me-1"></i>{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}</span>
                                                        @if ($etape->temperature_formatee)
                                                            <span><i class="fas fa-thermometer-half text-primary me-1"></i>{{ $etape->temperature_formatee }}</span>
                                                        @endif
                                                    </p>
                                                    @if ($etape->description)
                                                        <p class="mb-0 mt-2 text-muted">{{ $etape->description }}</p>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach

                        {{-- Carte du parcours : un marqueur par étape, reliés dans l'ordre chronologique --}}
                        @if ($points->isNotEmpty())
                            <div class="bg-light rounded p-4">
                                <h4 class="text-primary mb-3">Le parcours sur la carte</h4>
                                <div id="carte-tracabilite" class="rounded"></div>
                                <p class="mb-0 mt-2 text-muted"><small>Les numéros suivent l'ordre chronologique des étapes.</small></p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="text-center mt-5">
                    <a href="{{ route('tracabilite.index') }}" class="btn border-secondary rounded-pill px-5 py-3 text-primary">
                        <i class="fas fa-search me-2"></i>Suivre un autre lot
                    </a>
                </div>
            </div>
        </div>
        <!-- Traçabilité du lot End -->
@endsection

@push('styles')
    {{-- Leaflet (carte) depuis le CDN unpkg --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .tracabilite-rappel { background: #dc3545; border: 0; color: #fff; padding: 1.5rem 2rem; box-shadow: 0 .5rem 1.5rem rgba(220, 53, 69, .35); }
        .tracabilite-alerte { border: 0; color: #fff; padding: 1.5rem 2rem; }
        .tracabilite-alerte-danger { background: #dc3545; box-shadow: 0 .5rem 1.5rem rgba(220, 53, 69, .35); }
        .tracabilite-alerte-warning { background: #e67e22; box-shadow: 0 .5rem 1.5rem rgba(230, 126, 34, .35); }
        .tracabilite-image { height: 220px; object-fit: cover; }
        .tracabilite-infos th { width: 45%; font-weight: 400; color: #6c757d; padding-left: 0; }
        .tracabilite-infos td { padding-right: 0; }

        /* Jauge circulaire du score de transparence */
        .tracabilite-jauge {
            width: 120px; height: 120px; border-radius: 50%; flex-shrink: 0;
            background: conic-gradient(var(--couleur) calc(var(--score) * 1%), #e9ecef 0);
            display: flex; align-items: center; justify-content: center;
        }
        .tracabilite-jauge > span {
            width: 92px; height: 92px; border-radius: 50%; background: #fff;
            display: flex; align-items: center; justify-content: center;
        }
        .tracabilite-jauge strong { font-size: 2rem; line-height: 1; color: var(--couleur); }
        .tracabilite-jauge small { color: #6c757d; }
        .tracabilite-jauge-texte { min-width: 220px; flex-basis: 0; }
        .tracabilite-histoire { border-left: 5px solid #81c408; }

        /* Assistant : fil de discussion en bulles */
        .assistant-fil { max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; gap: .75rem; }
        .assistant-bulle { max-width: 85%; padding: .75rem 1rem; border-radius: 1.25rem; line-height: 1.5; white-space: pre-line; }
        .assistant-bulle-ia { align-self: flex-start; background: #fff; border-bottom-left-radius: .25rem; }
        .assistant-bulle-moi { align-self: flex-end; background: #81c408; color: #fff; border-bottom-right-radius: .25rem; }
        .assistant-bulle-erreur { align-self: flex-start; background: #fdecea; color: #842029; border-bottom-left-radius: .25rem; }

        /* Timeline verticale */
        .tracabilite-timeline { list-style: none; margin: 0; padding: 0 0 0 1.5rem; }
        .tracabilite-timeline li { position: relative; padding: 0 0 1.5rem 2.5rem; border-left: 3px solid #dee2e6; }
        .tracabilite-timeline li:last-child { border-left-color: transparent; padding-bottom: 0; }
        .tracabilite-point {
            position: absolute; left: -1.4rem; top: 0;
            width: 2.75rem; height: 2.75rem; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; border: 4px solid #f4f6f8;
        }

        /* Carte et marqueurs numérotés */
        #carte-tracabilite { height: 420px; z-index: 0; }
        .marqueur-etape {
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0, 0, 0, .4);
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Assistant traçabilité : envoi de la question en AJAX (fetch) et affichage en bulles
        document.addEventListener('DOMContentLoaded', function () {
            const assistant = document.getElementById('assistant');

            if (! assistant) {
                return;
            }

            const fil = document.getElementById('assistant-fil');
            const formulaire = document.getElementById('assistant-formulaire');
            const champ = document.getElementById('assistant-question');
            const bouton = document.getElementById('assistant-envoyer');

            // textContent : ni la question ni la réponse ne sont interprétées comme du HTML
            const ajouterBulle = (texte, type) => {
                const bulle = document.createElement('div');
                bulle.className = 'assistant-bulle assistant-bulle-' + type;
                bulle.textContent = texte;
                fil.appendChild(bulle);
                fil.scrollTop = fil.scrollHeight;
                return bulle;
            };

            const poser = async (question) => {
                question = question.trim();

                if (question === '' || bouton.disabled) {
                    return;
                }

                ajouterBulle(question, 'moi');
                champ.value = '';
                bouton.disabled = true;
                const attente = ajouterBulle('…', 'ia');

                try {
                    const reponse = await fetch(assistant.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': assistant.dataset.token,
                        },
                        body: JSON.stringify({ question }),
                    });
                    const donnees = await reponse.json().catch(() => ({}));

                    attente.remove();

                    if (reponse.status === 429) {
                        ajouterBulle('Trop de questions en peu de temps : patientez une minute avant de réessayer.', 'erreur');
                    } else if (reponse.status === 422) {
                        ajouterBulle(donnees.errors?.question?.[0] ?? 'Question invalide.', 'erreur');
                    } else {
                        ajouterBulle(donnees.reponse ?? 'Une erreur est survenue, réessayez.', reponse.ok ? 'ia' : 'erreur');
                    }
                } catch (erreur) {
                    attente.remove();
                    ajouterBulle('Connexion impossible : vérifiez votre réseau et réessayez.', 'erreur');
                } finally {
                    bouton.disabled = false;
                    champ.focus();
                }
            };

            formulaire.addEventListener('submit', (evenement) => {
                evenement.preventDefault();
                poser(champ.value);
            });

            document.querySelectorAll('.assistant-suggestion').forEach((suggestion) => {
                suggestion.addEventListener('click', () => poser(suggestion.textContent));
            });
        });
    </script>

    @if ($points->isNotEmpty())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Étapes géolocalisées, déjà dans l'ordre chronologique (préparées par TracabiliteController)
                const points = @json($points);

                // Couleurs Bootstrap du thème, pour retrouver sur la carte celles de la timeline
                const couleurs = { success: '#198754', warning: '#ffc107', info: '#0dcaf0', secondary: '#ffb524', primary: '#81c408', danger: '#dc3545' };

                const carte = L.map('carte-tracabilite', { scrollWheelZoom: false });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(carte);

                // Le texte passe par un élément DOM : aucun HTML saisi par un acteur n'est interprété
                const texte = (valeur) => {
                    const element = document.createElement('span');
                    element.textContent = valeur ?? '';
                    return element.innerHTML;
                };

                const trajet = [];

                points.forEach(function (point) {
                    // Deux étapes au même endroit : on décale légèrement le marqueur pour qu'il reste visible
                    const doublons = trajet.filter((p) => p[0] === point.lat && p[1] === point.lng).length;
                    const position = [point.lat + doublons * 0.012, point.lng + doublons * 0.012];

                    trajet.push([point.lat, point.lng]);

                    L.marker(position, {
                        icon: L.divIcon({
                            className: '',
                            html: '<div class="marqueur-etape" style="background:' + (couleurs[point.couleur] || '#6c757d') + '">' + point.rang + '</div>',
                            iconSize: [30, 30],
                            iconAnchor: [15, 15],
                        }),
                    }).addTo(carte).bindPopup(
                        '<strong>' + point.rang + '. ' + texte(point.titre) + '</strong><br>'
                        + texte(point.type) + ' · ' + texte(point.lieu) + '<br>'
                        + texte(point.date) + '<br>'
                        + texte(point.acteur) + '<br>'
                        + '<small>Lot ' + texte(point.lot) + '</small>'
                    );
                });

                // Ligne reliant les étapes dans l'ordre, puis cadrage sur l'ensemble du parcours
                const ligne = L.polyline(trajet, { color: '#81c408', weight: 4, opacity: 0.8, dashArray: '8 8' }).addTo(carte);
                carte.fitBounds(ligne.getBounds(), { padding: [40, 40], maxZoom: 11 });
            });
        </script>
    @endif
@endpush
