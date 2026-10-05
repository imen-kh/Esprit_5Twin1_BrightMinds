@extends('layouts.back')
@section('title', 'Nouvelle certification')
@section('content')
<div class="card">
    <div class="card-header"><h4>Ajouter une certification</h4></div>
    <div class="card-body">
        @include('admin.certifications.form')
    </div>
</div>
@endsection
