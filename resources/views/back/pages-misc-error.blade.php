@extends('layouts.back-blank')

@section('title', 'Pages Misc Error')

@section('content')
    <!-- Content -->

    <!-- Error -->
    <div class="container-xxl container-p-y">
      <div class="misc-wrapper">
        <h1 class="mb-2 mx-2" style="line-height: 6rem; font-size: 6rem">404</h1>
        <h4 class="mb-2 mx-2">Page Not Found️ ⚠️</h4>
        <p class="mb-6 mx-2">we couldn't find the page you are looking for</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Back to home</a>
        <div class="mt-6">
          <img
            src="{{ asset('back/assets/img/illustrations/page-misc-error-light.png') }}"
            alt="page-misc-error-light"
            width="500"
            class="img-fluid" />
        </div>
      </div>
    </div>
    <!-- /Error -->

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-misc.css') }}" />
@endpush
