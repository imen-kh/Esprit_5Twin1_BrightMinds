@extends('layouts.back')

@section('title', 'Exemple formulaire')

@section('content')
              <!-- Basic Layout -->
              <div class="row mb-6 gy-6">
                <div class="col-xl">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">Formulaire simple</h5>
                      <small class="text-body float-end">Libellés par défaut</small>
                    </div>
                    <div class="card-body">
                      <form>
                        <div class="mb-6">
                          <label class="form-label" for="basic-default-fullname">Nom complet</label>
                          <input type="text" class="form-control" id="basic-default-fullname" placeholder="John Doe" />
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-default-company">Entreprise</label>
                          <input type="text" class="form-control" id="basic-default-company" placeholder="ACME Inc." />
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-default-email">Email</label>
                          <div class="input-group input-group-merge">
                            <input
                              type="text"
                              id="basic-default-email"
                              class="form-control"
                              placeholder="john.doe"
                              aria-label="john.doe"
                              aria-describedby="basic-default-email2" />
                            <span class="input-group-text" id="basic-default-email2">@example.com</span>
                          </div>
                          <div class="form-text">Lettres, chiffres et points autorisés</div>
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-default-phone">Téléphone</label>
                          <input
                            type="text"
                            id="basic-default-phone"
                            class="form-control phone-mask"
                            placeholder="658 799 8941" />
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-default-message">Message</label>
                          <textarea
                            id="basic-default-message"
                            class="form-control"
                            placeholder="Bonjour, auriez-vous un moment pour échanger ?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                      </form>
                    </div>
                  </div>
                </div>
                <div class="col-xl">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">Formulaire avec icônes</h5>
                      <small class="text-body float-end">Champs avec icônes intégrées</small>
                    </div>
                    <div class="card-body">
                      <form>
                        <div class="mb-6">
                          <label class="form-label" for="basic-icon-default-fullname">Nom complet</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-fullname2" class="input-group-text"
                              ><i class="icon-base bx bx-user"></i
                            ></span>
                            <input
                              type="text"
                              class="form-control"
                              id="basic-icon-default-fullname"
                              placeholder="John Doe"
                              aria-label="John Doe"
                              aria-describedby="basic-icon-default-fullname2" />
                          </div>
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-icon-default-company">Entreprise</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"
                              ><i class="icon-base bx bx-buildings"></i
                            ></span>
                            <input
                              type="text"
                              id="basic-icon-default-company"
                              class="form-control"
                              placeholder="ACME Inc."
                              aria-label="ACME Inc."
                              aria-describedby="basic-icon-default-company2" />
                          </div>
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-icon-default-email">Email</label>
                          <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="icon-base bx bx-envelope"></i></span>
                            <input
                              type="text"
                              id="basic-icon-default-email"
                              class="form-control"
                              placeholder="john.doe"
                              aria-label="john.doe"
                              aria-describedby="basic-icon-default-email2" />
                            <span id="basic-icon-default-email2" class="input-group-text">@example.com</span>
                          </div>
                          <div class="form-text">Lettres, chiffres et points autorisés</div>
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-icon-default-phone">Téléphone</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-phone2" class="input-group-text"
                              ><i class="icon-base bx bx-phone"></i
                            ></span>
                            <input
                              type="text"
                              id="basic-icon-default-phone"
                              class="form-control phone-mask"
                              placeholder="658 799 8941"
                              aria-label="658 799 8941"
                              aria-describedby="basic-icon-default-phone2" />
                          </div>
                        </div>
                        <div class="mb-6">
                          <label class="form-label" for="basic-icon-default-message">Message</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-message2" class="input-group-text"
                              ><i class="icon-base bx bx-comment"></i
                            ></span>
                            <textarea
                              id="basic-icon-default-message"
                              class="form-control"
                              placeholder="Bonjour, auriez-vous un moment pour échanger ?"
                              aria-label="Bonjour, auriez-vous un moment pour échanger ?"
                              aria-describedby="basic-icon-default-message2"></textarea>
                          </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
@endsection
