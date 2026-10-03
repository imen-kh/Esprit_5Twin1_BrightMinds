@extends('layouts.back')

@section('title', 'Exemple tableau')

@section('content')
              <!-- Basic Bootstrap Table -->
              <div class="card">
                <h5 class="card-header">Tableau simple</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-2"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-2"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-2"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-2"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-2"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-2"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Basic Bootstrap Table -->

              <hr class="my-12" />

              <!-- Bootstrap Dark Table -->
              <div class="card overflow-hidden">
                <h5 class="card-header">Tableau sombre</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table table-dark">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td class="rounded-start-bottom">
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-3"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td class="rounded-end-bottom">
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Bootstrap Dark Table -->

              <hr class="my-12" />

              <!-- Bootstrap Table with Header - Dark -->
              <div class="card">
                <h5 class="card-header">En-tête sombre</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead class="table-dark">
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Bootstrap Table with Header Dark -->

              <hr class="my-12" />

              <!-- Bootstrap Table with Header - Light -->
              <div class="card">
                <h5 class="card-header">En-tête clair</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead class="table-light">
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Bootstrap Table with Header - Light -->

              <hr class="my-12" />

              <!-- Bootstrap Table with Header - Footer -->
              <div class="card">
                <h5 class="card-header">En-tête et pied de tableau</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="table-border-bottom-0">
                      <tr>
                        <th class="rounded-start-bottom">Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th class="rounded-end-bottom">Actions</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
              <!-- Bootstrap Table with Header - Footer -->

              <hr class="my-12" />

              <!-- Bootstrap Table with Caption -->
              <div class="card">
                <h5 class="card-header">Légende de tableau</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <caption class="ms-6">
                      Liste des projets
                    </caption>
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Bootstrap Table with Caption -->

              <hr class="my-12" />

              <!-- Striped Rows -->
              <div class="card">
                <h5 class="card-header">Lignes zébrées</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Striped Rows -->

              <hr class="my-12" />

              <!-- Bordered Table -->
              <div class="card">
                <h5 class="card-header">Tableau avec bordures</h5>
                <div class="card-body">
                  <div class="table-responsive text-nowrap">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>Projet</th>
                          <th>Client</th>
                          <th>Utilisateurs</th>
                          <th>Statut</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i>
                            <span>Projet Angular</span>
                          </td>
                          <td>Albert Cook</td>
                          <td>
                            <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Lilian Fuller">
                                <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Sophia Wilkerson">
                                <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Christina Parker">
                                <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                            </ul>
                          </td>
                          <td><span class="badge bg-label-primary me-1">Actif</span></td>
                          <td>
                            <div class="dropdown">
                              <button
                                type="button"
                                class="btn p-0 dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown">
                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                >
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                >
                              </div>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                          </td>
                          <td>Barry Hunter</td>
                          <td>
                            <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Lilian Fuller">
                                <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Sophia Wilkerson">
                                <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Christina Parker">
                                <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                            </ul>
                          </td>
                          <td><span class="badge bg-label-success me-1">Terminé</span></td>
                          <td>
                            <div class="dropdown">
                              <button
                                type="button"
                                class="btn p-0 dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown">
                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                >
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                >
                              </div>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                          </td>
                          <td>Trevor Baker</td>
                          <td>
                            <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Lilian Fuller">
                                <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Sophia Wilkerson">
                                <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Christina Parker">
                                <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                            </ul>
                          </td>
                          <td><span class="badge bg-label-info me-1">Planifié</span></td>
                          <td>
                            <div class="dropdown">
                              <button
                                type="button"
                                class="btn p-0 dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown">
                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                >
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                >
                              </div>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                            <span>Projet Bootstrap</span>
                          </td>
                          <td>Jerry Milton</td>
                          <td>
                            <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Lilian Fuller">
                                <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Sophia Wilkerson">
                                <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                              <li
                                data-bs-toggle="tooltip"
                                data-popup="tooltip-custom"
                                data-bs-placement="top"
                                class="avatar avatar-xs pull-up"
                                title="Christina Parker">
                                <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                              </li>
                            </ul>
                          </td>
                          <td><span class="badge bg-label-warning me-1">En attente</span></td>
                          <td>
                            <div class="dropdown">
                              <button
                                type="button"
                                class="btn p-0 dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown">
                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                              </button>
                              <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                >
                                <a class="dropdown-item" href="javascript:void(0);"
                                  ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                >
                              </div>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <!--/ Bordered Table -->

              <hr class="my-12" />

              <!-- Borderless Table -->
              <div class="card">
                <h5 class="card-header">Tableau sans bordures</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table table-borderless">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Borderless Table -->

              <hr class="my-12" />

              <!-- Hoverable Table rows -->
              <div class="card">
                <h5 class="card-header">Lignes survolables</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Hoverable Table rows -->

              <hr class="my-12" />

              <!-- Small table -->

              <div class="card">
                <h5 class="card-header">Tableau compact</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Small table -->

              <hr class="my-12" />

              <!-- Contextual Classes -->

              <div class="card">
                <h5 class="card-header">Couleurs contextuelles</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr class="table-default">
                        <td>
                          <i class="icon-base bx bxl-sketch icon-md text-warning me-4"></i> <span>Projet Sketch</span>
                        </td>
                        <td>Ronnie Shane</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-active">
                        <td>
                          <i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span>
                        </td>
                        <td>Barry Hunter</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-primary">
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-secondary">
                        <td>
                          <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                        </td>
                        <td>Trevor Baker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-success">
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-danger">
                        <td><i class="icon-base bx bxl-sketch text-warning me-4"></i> <span>Projet Sketch</span></td>
                        <td>Sarah Banks</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-warning">
                        <td><i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React sur mesure</span></td>
                        <td>Ted Richer</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-info me-1">Planifié</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-info">
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span>Dernière version de Bootstrap</span>
                        </td>
                        <td>Perry Parker</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-light">
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Interface Angular</span>
                        </td>
                        <td>Ana Bell</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr class="table-dark">
                        <td class="rounded-start-bottom">
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i> <span>Interface Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-success me-1">Terminé</span></td>
                        <td class="rounded-end-bottom">
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Contextual Classes -->

              <hr class="my-12" />

              <!-- Table within card -->
              <h5 class="mb-6">Tableau sans carte</h5>
              <div class="table-responsive text-nowrap">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Projet</th>
                      <th>Client</th>
                      <th>Utilisateurs</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody class="table-border-bottom-0">
                    <tr>
                      <td>
                        <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i> <span>Projet Angular</span>
                      </td>
                      <td>Albert Cook</td>
                      <td>
                        <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Lilian Fuller">
                            <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Sophia Wilkerson">
                            <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Christina Parker">
                            <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                        </ul>
                      </td>
                      <td><span class="badge bg-label-primary me-1">Actif</span></td>
                      <td>
                        <div class="dropdown">
                          <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                          </button>
                          <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                            >
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                            >
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td><i class="icon-base bx bxl-react icon-md text-info me-4"></i> <span>Projet React</span></td>
                      <td>Barry Hunter</td>
                      <td>
                        <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Lilian Fuller">
                            <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Sophia Wilkerson">
                            <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Christina Parker">
                            <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                        </ul>
                      </td>
                      <td><span class="badge bg-label-success me-1">Terminé</span></td>
                      <td>
                        <div class="dropdown">
                          <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                          </button>
                          <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                            >
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                            >
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i> <span>Projet VueJs</span>
                      </td>
                      <td>Trevor Baker</td>
                      <td>
                        <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Lilian Fuller">
                            <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Sophia Wilkerson">
                            <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Christina Parker">
                            <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                        </ul>
                      </td>
                      <td><span class="badge bg-label-info me-1">Planifié</span></td>
                      <td>
                        <div class="dropdown">
                          <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                          </button>
                          <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                            >
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                            >
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                        <span>Projet Bootstrap</span>
                      </td>
                      <td>Jerry Milton</td>
                      <td>
                        <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Lilian Fuller">
                            <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Sophia Wilkerson">
                            <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                          <li
                            data-bs-toggle="tooltip"
                            data-popup="tooltip-custom"
                            data-bs-placement="top"
                            class="avatar avatar-xs pull-up"
                            title="Christina Parker">
                            <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                          </li>
                        </ul>
                      </td>
                      <td><span class="badge bg-label-warning me-1">En attente</span></td>
                      <td>
                        <div class="dropdown">
                          <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                          </button>
                          <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                            >
                            <a class="dropdown-item" href="javascript:void(0);"
                              ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                            >
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!--/ Table within card -->

              <hr class="my-12" />

              <!-- Responsive Table -->
              <div class="card">
                <h5 class="card-header">Tableau adaptatif</h5>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead>
                      <tr class="text-nowrap">
                        <th>#</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                        <th>Titre de colonne</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <tr>
                        <th scope="row">1</th>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                      </tr>
                      <tr>
                        <th scope="row">2</th>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                      </tr>
                      <tr>
                        <th scope="row">3</th>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                        <td>Cellule</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Responsive Table -->

              <hr class="my-12" />

              <!-- Nested table -->
              <div class="card overflow-hidden">
                <h5 class="card-header">Tableau imbriqué</h5>
                <div class="table-responsive">
                  <table class="table table-dark">
                    <thead>
                      <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Utilisateurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-angular icon-md text-danger me-4"></i>
                          <span class="fw-medium">Projet Angular</span>
                        </td>
                        <td>Albert Cook</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1">Actif</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td colspan="5">
                          <table class="table mb-0 table-light">
                            <thead>
                              <tr>
                                <th>Projet</th>
                                <th>Client</th>
                                <th>Utilisateurs</th>
                                <th>Statut</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td>
                                  <i class="icon-base bx bxl-react icon-md text-info me-4"></i>
                                  <span class="fw-medium">Projet React</span>
                                </td>
                                <td>Barry Hunter</td>
                                <td>
                                  <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Lilian Fuller">
                                      <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Sophia Wilkerson">
                                      <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Christina Parker">
                                      <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                  </ul>
                                </td>
                                <td><span class="badge bg-label-success me-1">Terminé</span></td>
                                <td>
                                  <div class="dropdown">
                                    <button
                                      type="button"
                                      class="btn p-0 dropdown-toggle hide-arrow"
                                      data-bs-toggle="dropdown">
                                      <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                      <a class="dropdown-item" href="javascript:void(0);"
                                        ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                      >
                                      <a class="dropdown-item" href="javascript:void(0);"
                                        ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                      >
                                    </div>
                                  </div>
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <i class="icon-base bx bxl-vuejs icon-md text-success me-4"></i>
                                  <span class="fw-medium">Projet VueJs</span>
                                </td>
                                <td>Trevor Baker</td>
                                <td>
                                  <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Lilian Fuller">
                                      <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Sophia Wilkerson">
                                      <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li
                                      data-bs-toggle="tooltip"
                                      data-popup="tooltip-custom"
                                      data-bs-placement="top"
                                      class="avatar avatar-xs pull-up"
                                      title="Christina Parker">
                                      <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                                    </li>
                                  </ul>
                                </td>
                                <td><span class="badge bg-label-info me-1">Planifié</span></td>
                                <td>
                                  <div class="dropdown">
                                    <button
                                      type="button"
                                      class="btn p-0 dropdown-toggle hide-arrow"
                                      data-bs-toggle="dropdown">
                                      <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                      <a class="dropdown-item" href="javascript:void(0);"
                                        ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                                      >
                                      <a class="dropdown-item" href="javascript:void(0);"
                                        ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                                      >
                                    </div>
                                  </div>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="icon-base bx bxl-bootstrap icon-md text-primary me-4"></i>
                          <span class="fw-medium">Projet Bootstrap</span>
                        </td>
                        <td>Jerry Milton</td>
                        <td>
                          <ul class="list-unstyled m-0 avatar-group d-flex align-items-center">
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Lilian Fuller">
                              <img src="{{ asset('back/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Sophia Wilkerson">
                              <img src="{{ asset('back/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                            <li
                              data-bs-toggle="tooltip"
                              data-popup="tooltip-custom"
                              data-bs-placement="top"
                              class="avatar avatar-xs pull-up"
                              title="Christina Parker">
                              <img src="{{ asset('back/assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle" />
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-warning me-1">En attente</span></td>
                        <td>
                          <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="icon-base bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-edit-alt me-1"></i> Modifier</a
                              >
                              <a class="dropdown-item" href="javascript:void(0);"
                                ><i class="icon-base bx bx-trash me-1"></i> Supprimer</a
                              >
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Nested table -->
@endsection
