@extends('layouts.back-blank')

@section('title', 'Pages Misc Under Maintenance')

@section('content')
    <!-- Content -->

    <!--Under Maintenance -->
    <div class="container-xxl container-p-y">
      <div class="misc-wrapper">
        <h3 class="mb-2 mx-2">Under Maintenance! 🚧</h3>
        <p class="mb-6 mx-2">Sorry for the inconvenience but we're performing some maintenance at the moment</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Back to home</a>
        <div class="mt-6">
          <img
            src="{{ asset('back/assets/img/illustrations/girl-doing-yoga-light.png') }}"
            alt="girl-doing-yoga-light"
            width="500"
            class="img-fluid" />
        </div>
      </div>
    </div>
    <!-- /Under Maintenance -->

    <!-- / Content -->

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('back/assets/vendor/css/pages/page-misc.css') }}" />
@endpush
