@extends('layouts.back')

@section('title', 'Nouveau lot')

@section('content')
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('gestion.lots.index') }}">Lots</a></li>
                  <li class="breadcrumb-item active">Nouveau lot</li>
                </ol>
              </nav>

              <div class="card">
                <h5 class="card-header">Nouveau lot</h5>
                <div class="card-body">
                  {{-- novalidate : on laisse la validation serveur (StoreLotRequest) afficher les erreurs --}}
                  <form method="POST" action="{{ route('gestion.lots.store') }}" novalidate>
                    @csrf
                    @include('back.lots._form')
                  </form>
                </div>
              </div>
@endsection
