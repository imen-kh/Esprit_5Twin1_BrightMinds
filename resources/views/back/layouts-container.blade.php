@extends('layouts.back')

@section('title', 'Layouts Container')

@section('content')
              <!-- Layout Demo -->
              <div class="layout-demo-wrapper">
                <div class="layout-demo-placeholder">
                  <img
                    src="{{ asset('back/assets/img/layouts/layout-container-light.png') }}"
                    class="img-fluid"
                    alt="Layout container" />
                </div>
                <div class="layout-demo-info">
                  <h4>Layout container</h4>
                  <p>Container layout sets a <code>max-width</code> at each responsive breakpoint.</p>
                </div>
              </div>
              <!--/ Layout Demo -->
@endsection
