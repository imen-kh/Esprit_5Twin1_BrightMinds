        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
              <span class="app-brand-logo demo">
                <span class="text-primary">
                  <svg
                    width="25"
                    viewBox="0 0 25 42"
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs>
                      <path
                        d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                        id="path-1"></path>
                      <path
                        d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                        id="path-3"></path>
                      <path
                        d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                        id="path-4"></path>
                      <path
                        d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                        id="path-5"></path>
                    </defs>
                    <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                        <g id="Icon" transform="translate(27.000000, 15.000000)">
                          <g id="Mask" transform="translate(0.000000, 8.000000)">
                            <mask id="mask-2" fill="white">
                              <use xlink:href="#path-1"></use>
                            </mask>
                            <use fill="currentColor" xlink:href="#path-1"></use>
                            <g id="Path-3" mask="url(#mask-2)">
                              <use fill="currentColor" xlink:href="#path-3"></use>
                              <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                            </g>
                            <g id="Path-4" mask="url(#mask-2)">
                              <use fill="currentColor" xlink:href="#path-4"></use>
                              <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                            </g>
                          </g>
                          <g
                            id="Triangle"
                            transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                            <use fill="currentColor" xlink:href="#path-5"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                          </g>
                        </g>
                      </g>
                    </g>
                  </svg>
                </span>
              </span>
              <span class="app-brand-text demo menu-text fw-bold ms-2">NutriTrace</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
              <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
            </a>
          </div>

          <div class="menu-divider mt-0"></div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboards -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div class="text-truncate" data-i18n="Dashboards">Tableaux de bord</div>
                <span class="badge rounded-pill bg-danger ms-auto">5</span>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                  <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Analytics">Statistiques</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/dashboards-crm.html"
                    target="_blank"
                    class="menu-link">
                    <div class="text-truncate" data-i18n="CRM">CRM</div>
                    <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-ecommerce-dashboard.html"
                    target="_blank"
                    class="menu-link">
                    <div class="text-truncate" data-i18n="eCommerce">eCommerce</div>
                    <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-logistics-dashboard.html"
                    target="_blank"
                    class="menu-link">
                    <div class="text-truncate" data-i18n="Logistics">Logistique</div>
                    <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-academy-dashboard.html"
                    target="_blank"
                    class="menu-link">
                    <div class="text-truncate" data-i18n="Academy">Formation</div>
                    <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
                  </a>
                </li>
              </ul>
            </li>


            <li class="menu-header small text-uppercase"><span class="menu-header-text">Modules NutriTrace</span></li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-category"></i>
                <div class="text-truncate">Catalogue</div>
              </a>
              <ul class="menu-sub">
                @if (auth()->user()->isAdmin())
                  <li class="menu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><a href="{{ route('admin.categories.index') }}" class="menu-link"><div class="text-truncate">Catégories</div></a></li>
                  <li class="menu-item {{ request()->routeIs('admin.produits.*') ? 'active' : '' }}"><a href="{{ route('admin.produits.index') }}" class="menu-link"><div class="text-truncate">Produits</div></a></li>
                @endif
              </ul>
            </li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-git-branch"></i>
                <div class="text-truncate">Traçabilité</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item"><a href="#" class="menu-link"><div class="text-truncate">Lots</div></a></li>
                <li class="menu-item"><a href="#" class="menu-link"><div class="text-truncate">Étapes</div></a></li>
              </ul>
            </li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-badge-check"></i>
                <div class="text-truncate">Certifications</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item"><a href="#" class="menu-link"><div class="text-truncate">Labels</div></a></li>
                <li class="menu-item"><a href="#" class="menu-link"><div class="text-truncate">Certifications</div></a></li>
              </ul>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.facteurs-emission.*') || request()->routeIs('admin.bilans-environnementaux.*') ? 'active open' : '' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-leaf text-success"></i>
                <div class="text-truncate">Environnement</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.facteurs-emission.*') ? 'active' : '' }}"><a href="{{ route('admin.facteurs-emission.index') }}" class="menu-link"><div class="text-truncate">Facteurs d'émission</div></a></li>
                <li class="menu-item {{ request()->routeIs('admin.bilans-environnementaux.*') ? 'active' : '' }}"><a href="{{ route('admin.bilans-environnementaux.index') }}" class="menu-link"><div class="text-truncate">Bilans</div></a></li>
              </ul>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reclamations.*') ? 'active open' : '' }}">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-message-square-error"></i>
                <div class="text-truncate">Signalements</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.reclamations.*') ? 'active' : '' }}"><a href="{{ route('admin.reclamations.index') }}" class="menu-link"><div class="text-truncate">Réclamations</div></a></li>
              </ul>
            </li>
            <li class="menu-item">
              <a href="{{ route('home') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-arrow-back"></i>
                <div class="text-truncate">Retour au site</div>
              </a>
            </li>

            <!-- Layouts -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div class="text-truncate" data-i18n="Layouts">Mises en page</div>
              </a>

              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.layouts-without-menu') ? 'active' : '' }}">
                  <a href="{{ route('admin.layouts-without-menu') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Without menu">Sans menu</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.layouts-without-navbar') ? 'active' : '' }}">
                  <a href="{{ route('admin.layouts-without-navbar') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Without navbar">Sans barre de navigation</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.layouts-fluid') ? 'active' : '' }}">
                  <a href="{{ route('admin.layouts-fluid') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Fluid">Fluide</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.layouts-container') ? 'active' : '' }}">
                  <a href="{{ route('admin.layouts-container') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Container">Conteneur</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.layouts-blank') ? 'active' : '' }}">
                  <a href="{{ route('admin.layouts-blank') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Blank">Page vide</div>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Front Pages -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-store"></i>
                <div class="text-truncate" data-i18n="Front Pages">Pages publiques</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/front-pages/landing-page.html"
                    class="menu-link"
                    target="_blank">
                    <div class="text-truncate" data-i18n="Landing">Page d'accueil</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/front-pages/pricing-page.html"
                    class="menu-link"
                    target="_blank">
                    <div class="text-truncate" data-i18n="Pricing">Tarifs</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/front-pages/payment-page.html"
                    class="menu-link"
                    target="_blank">
                    <div class="text-truncate" data-i18n="Payment">Paiement</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/front-pages/checkout-page.html"
                    class="menu-link"
                    target="_blank">
                    <div class="text-truncate" data-i18n="Checkout">Commande</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a
                    href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/front-pages/help-center-landing.html"
                    class="menu-link"
                    target="_blank">
                    <div class="text-truncate" data-i18n="Help Center">Centre d'aide</div>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Apps & Pages -->
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Applications et pages</span>
            </li>
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-email.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-envelope"></i>
                <div class="text-truncate" data-i18n="Email">Email</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-chat.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-chat"></i>
                <div class="text-truncate" data-i18n="Chat">Messagerie</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-calendar.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar"></i>
                <div class="text-truncate" data-i18n="Calendar">Calendrier</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/app-kanban.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-grid"></i>
                <div class="text-truncate" data-i18n="Kanban">Kanban</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <!-- Pages -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div class="text-truncate" data-i18n="Account Settings">Paramètres du compte</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.pages-account-settings-account') ? 'active' : '' }}">
                  <a href="{{ route('admin.pages-account-settings-account') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Account">Compte</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.pages-account-settings-notifications') ? 'active' : '' }}">
                  <a href="{{ route('admin.pages-account-settings-notifications') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Notifications">Notifications</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.pages-account-settings-connections') ? 'active' : '' }}">
                  <a href="{{ route('admin.pages-account-settings-connections') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Connections">Connexions</div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div class="text-truncate" data-i18n="Authentications">Authentification</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.auth-login-basic') ? 'active' : '' }}">
                  <a href="{{ route('admin.auth-login-basic') }}" class="menu-link" target="_blank">
                    <div class="text-truncate" data-i18n="Basic">Connexion</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.auth-register-basic') ? 'active' : '' }}">
                  <a href="{{ route('admin.auth-register-basic') }}" class="menu-link" target="_blank">
                    <div class="text-truncate" data-i18n="Basic">Inscription</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.auth-forgot-password-basic') ? 'active' : '' }}">
                  <a href="{{ route('admin.auth-forgot-password-basic') }}" class="menu-link" target="_blank">
                    <div class="text-truncate" data-i18n="Basic">Mot de passe oublié</div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cube-alt"></i>
                <div class="text-truncate" data-i18n="Misc">Divers</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.pages-misc-error') ? 'active' : '' }}">
                  <a href="{{ route('admin.pages-misc-error') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Error">Erreur</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.pages-misc-under-maintenance') ? 'active' : '' }}">
                  <a href="{{ route('admin.pages-misc-under-maintenance') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Under Maintenance">En maintenance</div>
                  </a>
                </li>
              </ul>
            </li>
            <!-- Components -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Composants</span></li>
            <!-- Cards -->
            <li class="menu-item {{ request()->routeIs('admin.cards-basic') ? 'active' : '' }}">
              <a href="{{ route('admin.cards-basic') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-collection"></i>
                <div class="text-truncate" data-i18n="Basic">Cartes</div>
              </a>
            </li>
            <!-- User interface -->
            <li class="menu-item">
              <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-box"></i>
                <div class="text-truncate" data-i18n="User interface">Interface utilisateur</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.ui-accordion') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-accordion') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Accordion">Accordéon</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-alerts') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-alerts') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Alerts">Alertes</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-badges') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-badges') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Badges">Badges</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-buttons') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-buttons') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Buttons">Boutons</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-carousel') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-carousel') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Carousel">Carrousel</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-collapse') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-collapse') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Collapse">Repli</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-dropdowns') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-dropdowns') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Dropdowns">Menus déroulants</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-footer') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-footer') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Footer">Pied de page</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-list-groups') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-list-groups') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="List Groups">Listes groupées</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-modals') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-modals') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Modals">Fenêtres modales</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-navbar') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-navbar') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Navbar">Barre de navigation</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-offcanvas') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-offcanvas') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Offcanvas">Panneau latéral</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-pagination-breadcrumbs') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-pagination-breadcrumbs') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Pagination & Breadcrumbs">Pagination et fil d'Ariane</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-progress') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-progress') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Progress">Barres de progression</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-spinners') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-spinners') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Spinners">Indicateurs de chargement</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-tabs-pills') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-tabs-pills') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Tabs & Pills">Onglets</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-toasts') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-toasts') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Toasts">Toasts</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-tooltips-popovers') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-tooltips-popovers') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Tooltips & Popovers">Infobulles</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.ui-typography') ? 'active' : '' }}">
                  <a href="{{ route('admin.ui-typography') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Typography">Typographie</div>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Extended components -->
            <li class="menu-item">
              <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-copy"></i>
                <div class="text-truncate" data-i18n="Extended UI">Interface étendue</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.extended-ui-perfect-scrollbar') ? 'active' : '' }}">
                  <a href="{{ route('admin.extended-ui-perfect-scrollbar') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Perfect Scrollbar">Barre de défilement</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.extended-ui-text-divider') ? 'active' : '' }}">
                  <a href="{{ route('admin.extended-ui-text-divider') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Text Divider">Séparateur de texte</div>
                  </a>
                </li>
              </ul>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.icons-boxicons') ? 'active' : '' }}">
              <a href="{{ route('admin.icons-boxicons') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-crown"></i>
                <div class="text-truncate" data-i18n="Boxicons">Icônes</div>
              </a>
            </li>

            <!-- Forms & Tables -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Formulaires et tableaux</span></li>
            <!-- Forms -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-detail"></i>
                <div class="text-truncate" data-i18n="Form Elements">Éléments de formulaire</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.forms-basic-inputs') ? 'active' : '' }}">
                  <a href="{{ route('admin.forms-basic-inputs') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Basic Inputs">Champs de base</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.forms-input-groups') ? 'active' : '' }}">
                  <a href="{{ route('admin.forms-input-groups') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Input groups">Groupes de champs</div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-detail"></i>
                <div class="text-truncate" data-i18n="Form Layouts">Mises en page de formulaire</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.form-layouts-vertical') ? 'active' : '' }}">
                  <a href="{{ route('admin.form-layouts-vertical') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Vertical Form">Formulaire vertical</div>
                  </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.form-layouts-horizontal') ? 'active' : '' }}">
                  <a href="{{ route('admin.form-layouts-horizontal') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="Horizontal Form">Formulaire horizontal</div>
                  </a>
                </li>
              </ul>
            </li>
            <!-- Form Validation -->
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/form-validation.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-list-check"></i>
                <div class="text-truncate" data-i18n="Form Validation">Validation de formulaire</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <!-- Tables -->
            <li class="menu-item {{ request()->routeIs('admin.tables-basic') ? 'active' : '' }}">
              <a href="{{ route('admin.tables-basic') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div class="text-truncate" data-i18n="Tables">Tableaux</div>
              </a>
            </li>
            <!-- Data Tables -->
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/html/vertical-menu-template/tables-datatables-basic.html"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-grid"></i>
                <div class="text-truncate" data-i18n="Datatables">Tableaux de données</div>
                <div class="badge rounded-pill bg-label-primary text-uppercase fs-tiny ms-auto">Pro</div>
              </a>
            </li>
            <!-- Misc -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Divers</span></li>
            <li class="menu-item">
              <a
                href="https://github.com/themeselection/sneat-bootstrap-html-admin-template-free/issues"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-support"></i>
                <div class="text-truncate" data-i18n="Support">Assistance</div>
              </a>
            </li>
            <li class="menu-item">
              <a
                href="https://demos.themeselection.com/sneat-bootstrap-html-admin-template/documentation/"
                target="_blank"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div class="text-truncate" data-i18n="Documentation">Documentation</div>
              </a>
            </li>
          </ul>
        </aside>
        <!-- / Menu -->
