        <!-- Navbar start -->
        <div class="container-fluid fixed-top">
            <div class="container topbar bg-primary d-none d-lg-block">
                <div class="d-flex justify-content-between">
                    <div class="top-info ps-2">
                        <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#" class="text-white">Tunis, Tunisie</a></small>
                        <small class="me-3"><i class="fas fa-envelope me-2 text-secondary"></i><a href="#" class="text-white">contact@nutritrace.tn</a></small>
                    </div>
                    <div class="top-link pe-2">
                        <a href="#" class="text-white"><small class="text-white mx-2">Politique de confidentialité</small>/</a>
                        <a href="#" class="text-white"><small class="text-white mx-2">Conditions d'utilisation</small>/</a>
                        <a href="#" class="text-white"><small class="text-white ms-2">Ventes et remboursements</small></a>
                    </div>
                </div>
            </div>
            <div class="container px-0">
                <nav class="navbar navbar-light bg-white navbar-expand-xl">
                    <a href="{{ route('home') }}" class="navbar-brand"><h1 class="text-primary display-6">NutriTrace</h1></a>
                    <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars text-primary"></span>
                    </button>
                    <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                        <div class="navbar-nav mx-auto">
                            <a href="{{ route('home') }}" class="nav-item nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Accueil</a>
                            <a href="{{ route('produits') }}" class="nav-item nav-link {{ request()->routeIs('produits') ? 'active' : '' }}">Produits</a>
                            {{-- module Traçabilité : page publique de suivi des lots --}}
                            <a href="{{ route('tracabilite.index') }}" class="nav-item nav-link {{ request()->routeIs('tracabilite.*') ? 'active' : '' }}">Traçabilité</a>
                            <a href="{{ route('labels.index') }}" class="nav-item nav-link {{ request()->routeIs('labels.*') ? 'active' : '' }}">Labels</a>
                            <a href="{{ route('front.environnement.index') }}" class="nav-item nav-link {{ request()->routeIs('front.environnement.*') ? 'active' : '' }}">Éco-Score</a>
                            @auth
                                @if (auth()->user()->isProducteur())
                                    <a href="{{ route('acteur.produits.index') }}" class="nav-item nav-link {{ request()->routeIs('acteur.produits.*') ? 'active' : '' }}">Ajouter un produit</a>
                                    <a href="{{ route('acteur.production-planner') }}" class="nav-item nav-link {{ request()->routeIs('acteur.production-planner*') ? 'active' : '' }}">Prévisions de demande</a>
                                @elseif (auth()->user()->isTransformateur())
                                    <a href="{{ route('acteur.transformation') }}" class="nav-item nav-link {{ request()->routeIs('acteur.transformation') ? 'active' : '' }}">Transformation</a>
                                    <a href="{{ route('acteur.production-planner') }}" class="nav-item nav-link {{ request()->routeIs('acteur.production-planner*') ? 'active' : '' }}">Prévisions de production</a>
                                @elseif (auth()->user()->isDistributeur())
                                    <a href="{{ route('acteur.distribution') }}" class="nav-item nav-link {{ request()->routeIs('acteur.distribution') ? 'active' : '' }}">Distribution</a>
                                @endif
                                {{-- module Traçabilité : gestion des lots et des étapes, pour tous les acteurs --}}
                                @if (auth()->user()->isActeur())
                                    <a href="{{ route('gestion.lots.index') }}" class="nav-item nav-link">Mes lots</a>
                                    <a href="{{ route('acteur.certifications.index') }}" class="nav-item nav-link {{ request()->routeIs('acteur.certifications.*') ? 'active' : '' }}">Mes certifications</a>
                                @endif
                                <a href="{{ route('reclamations.index') }}" class="nav-item nav-link {{ request()->routeIs('reclamations.*') ? 'active' : '' }}">Réclamations</a>
                            @endauth
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                    <a href="{{ route('panier') }}" class="dropdown-item">Panier</a>
                                    <a href="{{ route('commande') }}" class="dropdown-item">Commande</a>
                                    @auth
                                        @if (auth()->user()->isTransformateur())
                                            <a href="{{ route('commandes.index') }}" class="dropdown-item">Mes commandes</a>
                                    <a href="{{ route('acteur.produits.index') }}" class="dropdown-item">Mes produits transformés</a>
                                        @elseif (auth()->user()->role === 'consommateur')
                                            <a href="{{ route('commandes.index') }}" class="dropdown-item">Mes commandes</a>
                                        @elseif (auth()->user()->isDistributeur())
                                            <a href="{{ route('distributeur.dashboard') }}" class="dropdown-item">Tableau de bord</a>
                                            <a href="{{ route('distributeur.commandes.index') }}" class="dropdown-item">Commandes à traiter</a>
                                            <a href="{{ route('distributeur.livraisons.index') }}" class="dropdown-item">Mes livraisons</a>
                                        @endif
                                    @endauth
                                    <a href="{{ route('temoignages') }}" class="dropdown-item">Témoignages</a>
                                    <a href="{{ route('page404') }}" class="dropdown-item">Page 404</a>
                                </div>
                            </div>
                            <a href="{{ route('contact') }}" class="nav-item nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>
                        </div>
                        <div class="d-flex m-3 me-0">
                            <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search text-primary"></i></button>
                            @if (Auth::check() && (Auth::user()->isConsommateur() || Auth::user()->isTransformateur()))
                                @php
                                    $cart = \App\Models\Panier::where('user_id', Auth::id())->where('statut', 'actif')->withCount('products')->first();
                                    $cart_count = $cart?->products_count ?? 0;
                                @endphp
                                <a href="{{ route('panier') }}" class="position-relative me-4 my-auto">
                                    <i class="fa fa-shopping-bag fa-2x"></i>
                                    <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;">{{ $cart_count }}</span>
                                </a>
                            @endif
                            @guest
                                <a href="{{ route('login') }}" class="my-auto" title="Se connecter">
                                    <i class="fas fa-user fa-2x"></i>
                                </a>
                            @else
                                {{-- Cloche de notifications --}}
                                @php
                                    $nbNonLues = auth()->user()->unreadNotifications->count();
                                    $recentes  = auth()->user()->notifications()->latest()->take(5)->get();
                                @endphp
                                <div class="nav-item dropdown my-auto me-3 position-relative">
                                    <a href="javascript:void(0);" class="position-relative" data-bs-toggle="dropdown"
                                       id="notif-bell-front" aria-label="Notifications">
                                        <i class="fas fa-bell fa-2x"></i>
                                        @if ($nbNonLues > 0)
                                            <span class="position-absolute bg-danger rounded-circle d-flex align-items-center justify-content-center text-white px-1"
                                                  style="top:-6px;left:14px;height:20px;min-width:20px;font-size:.65rem;">{{ $nbNonLues }}</span>
                                        @endif
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end p-0 mt-2" style="width:310px;max-height:400px;overflow-y:auto;left:auto;right:0;">
                                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                            <strong style="font-size:.9rem;">Notifications</strong>
                                            @if ($nbNonLues > 0)
                                                <form method="POST" action="{{ route('notifications.tout-lire') }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Tout lire</button>
                                                </form>
                                            @endif
                                        </div>
                                        @forelse ($recentes as $notif)
                                            @php $data = $notif->data; @endphp
                                            <a href="{{ $data['url'] ?? '#' }}"
                                               class="dropdown-item py-2 px-3 border-bottom {{ $notif->read_at ? '' : 'bg-light' }}"
                                               style="white-space:normal;">
                                                <div class="d-flex align-items-start gap-2">
                                                    <i class="fas {{ str_starts_with($data['type'] ?? '', 'livraison') ? 'fa-shipping-fast' : 'fa-shopping-bag' }} text-primary mt-1"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold" style="font-size:.82rem;">{{ $data['titre'] ?? 'Notification' }}</div>
                                                        <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    @if (!$notif->read_at)
                                                        <form method="POST" action="{{ route('notifications.lire', $notif->id) }}" class="ms-auto">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="btn btn-sm p-0 text-muted" title="Marquer comme lue">&times;</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </a>
                                        @empty
                                            <p class="text-muted text-center py-3 mb-0" style="font-size:.85rem;">Aucune notification</p>
                                        @endforelse
                                        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center py-2 text-primary border-top">Voir tout l'historique</a>
                                    </div>
                                </div>

                                {{-- Utilisateur connecté : menu avec son nom --}}
                                <div class="nav-item dropdown my-auto">
                                    <a href="#" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="fas fa-user fa-2x me-2"></i>
                                        <span class="d-flex flex-column lh-sm">
                                            <span>{{ auth()->user()->name }}</span>
                                            {{-- Rôle, suivi du type d'acteur s'il existe --}}
                                            <small class="text-muted">
                                                {{ ucfirst(auth()->user()->role) }}@if (auth()->user()->type_acteur) · {{ ucfirst(auth()->user()->type_acteur) }}@endif
                                            </small>
                                        </span>
                                    </a>
                                    <div class="dropdown-menu m-0 bg-secondary rounded-0" style="left: auto; right: 0;">
                                        <a href="{{ route('profil.edit') }}" class="dropdown-item">Mon profil</a>
                                        @if (auth()->user()->isAdmin())
                                            <a href="{{ route('admin.dashboard') }}" class="dropdown-item">Back office</a>
                                        @endif
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">D&eacute;connexion</button>
                                        </form>
                                    </div>
                                </div>
                            @endguest
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Navbar End -->


        <!-- Modal Search Start -->
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Rechercher par mot-clé</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input type="search" class="form-control p-3" placeholder="mots-clés" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Search End -->

