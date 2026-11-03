          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
              <!-- Search -->
              <div class="navbar-nav align-items-center me-auto">
                <div class="nav-item d-flex align-items-center">
                  <span class="w-px-22 h-px-22"><i class="icon-base bx bx-search icon-md"></i></span>
                  <input
                    type="text"
                    class="form-control border-0 shadow-none ps-1 ps-sm-2 d-md-block d-none"
                    placeholder="Rechercher..."
                    aria-label="Rechercher..." />
                </div>
              </div>
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-md-auto">

                @auth
                @php
                  $nbNonLues = auth()->user()->unreadNotifications->count();
                  $recentes  = auth()->user()->notifications()->latest()->take(5)->get();
                @endphp
                {{-- Cloche de notifications --}}
                <li class="nav-item dropdown me-3">
                  <a class="nav-link position-relative" href="javascript:void(0);"
                     data-bs-toggle="dropdown" aria-expanded="false"
                     id="notif-bell-admin" aria-label="Notifications">
                    <i class="icon-base bx bx-bell icon-md"></i>
                    @if ($nbNonLues > 0)
                      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size:0.65rem;min-width:18px;">{{ $nbNonLues }}</span>
                    @endif
                  </a>
                  <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px;max-height:420px;overflow-y:auto;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                      <strong class="text-sm">Notifications</strong>
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
                          <i class="bx {{ str_starts_with($data['type'] ?? '', 'livraison') ? 'bx-package' : 'bx-cart' }} text-primary mt-1"></i>
                          <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.85rem;">{{ $data['titre'] ?? 'Notification' }}</div>
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
                </li>
                @endauth

                {{-- User --}}
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="{{ auth()->user()->avatar_url }}" alt class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="{{ route('profil.edit') }}">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ auth()->user()->avatar_url }}" alt class="w-px-40 h-px-40 rounded-circle" style="object-fit: cover;" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                            {{-- Rôle, suivi du type d'acteur s'il existe --}}
                            <small class="text-body-secondary">{{ ucfirst(auth()->user()->role) }}@if (auth()->user()->type_acteur) · {{ ucfirst(auth()->user()->type_acteur) }}@endif</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('profil.edit') }}">
                        <i class="icon-base bx bx-user icon-md me-3"></i><span>Mon profil</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#">
                        <i class="icon-base bx bx-cog icon-md me-3"></i><span>Paramètres</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#">
                        <span class="d-flex align-items-center align-middle">
                          <i class="flex-shrink-0 icon-base bx bx-credit-card icon-md me-3"></i
                          ><span class="flex-grow-1 align-middle">Facturation</span>
                          <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
                        </span>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                      {{-- Déconnexion en POST (protégée par @csrf) --}}
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                          <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Déconnexion</span>
                        </button>
                      </form>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->
