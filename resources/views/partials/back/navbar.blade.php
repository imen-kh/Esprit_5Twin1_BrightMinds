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

                <!-- User -->
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
