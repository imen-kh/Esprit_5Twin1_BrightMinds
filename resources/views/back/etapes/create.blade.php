@extends('layouts.back')

@section('title', 'Nouvelle étape')

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.etapes.index') }}">Étapes</a></li>
                  <li class="breadcrumb-item active">Nouvelle étape</li>
                </ol>
              </nav>

              <div class="card">
                <h5 class="card-header">Nouvelle étape de traçabilité</h5>
                <div class="card-body">
                  {{-- novalidate : on laisse la validation serveur (StoreEtapeRequest) afficher les erreurs --}}
                  <form method="POST" action="{{ route('gestion.etapes.store') }}" novalidate>
                    @csrf
                    @include('back.etapes._form')
                  </form>
                </div>
              </div>
@endsection
