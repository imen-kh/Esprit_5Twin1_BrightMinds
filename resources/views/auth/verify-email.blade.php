@extends('layouts.back-blank')

@section('title', 'Vérifiez votre email')

@section('content')
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Vérification de l'email -->
          <div class="card px-sm-6 px-0">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center mb-6">
                <a href="{{ route('home') }}" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                    <span class="text-primary">
                      @include('partials.back.logo')
                    </span>
                  </span>
                  <span class="app-brand-text demo text-heading fw-bold">NutriTrace</span>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-1">Vérifiez votre email ✉️</h4>
              <p class="mb-6">
                Un lien de confirmation a été envoyé à <strong>{{ auth()->user()->email }}</strong>.
                Cliquez dessus pour activer votre compte et accéder à votre espace. Pensez à regarder dans les courriers indésirables.
              </p>

              @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
              @endif

              @error('email')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
              @enderror

              {{-- Renvoi du lien (6 envois par minute au maximum) --}}
              <form method="POST" action="{{ route('verification.send') }}" class="mb-6">
                @csrf
                <button type="submit" class="btn btn-primary d-grid w-100">Renvoyer l'email de vérification</button>
              </form>

              <div class="text-center">
                <a href="{{ route('logout') }}" class="d-flex justify-content-center">
                  <i class="icon-base bx bx-log-out me-1"></i>
                  Se déconnecter
                </a>
              </div>
            </div>
          </div>
          <!-- /Vérification de l'email -->
        </div>
      </div>
    </div>

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-auth.css') }}" />
@endpush
