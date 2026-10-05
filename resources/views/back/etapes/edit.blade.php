@extends('layouts.back')

@section('title', 'Modifier l\'étape')

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.etapes.index') }}">Étapes</a></li>
                  <li class="breadcrumb-item"><a href="{{ route('gestion.etapes.show', $etape) }}">{{ \Illuminate\Support\Str::limit($etape->titre, 40) }}</a></li>
                  <li class="breadcrumb-item active">Modifier</li>
                </ol>
              </nav>

              <div class="card">
                <h5 class="card-header">Modifier l'étape</h5>
                <div class="card-body">
                  {{-- novalidate : on laisse la validation serveur (UpdateEtapeRequest) afficher les erreurs --}}
                  <form method="POST" action="{{ route('gestion.etapes.update', $etape) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('back.etapes._form')
                  </form>
                </div>
              </div>
@endsection
