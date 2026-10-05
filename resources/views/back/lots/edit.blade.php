@extends('layouts.back')

@section('title', 'Modifier le lot '.$lot->code)

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.lots.index') }}">Lots</a></li>
                  <li class="breadcrumb-item"><a href="{{ route('gestion.lots.show', $lot) }}">{{ $lot->code }}</a></li>
                  <li class="breadcrumb-item active">Modifier</li>
                </ol>
              </nav>

              <div class="card">
                <h5 class="card-header">Modifier le lot {{ $lot->code }}</h5>
                <div class="card-body">
                  {{-- novalidate : on laisse la validation serveur (UpdateLotRequest) afficher les erreurs --}}
                  <form method="POST" action="{{ route('gestion.lots.update', $lot) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('back.lots._form')
                  </form>
                </div>
              </div>
@endsection
