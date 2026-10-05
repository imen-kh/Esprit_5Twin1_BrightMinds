@extends('layouts.back')
@section('title','Nouveau label')
@section('content')<div class="card"><div class="card-header"><h4>Nouveau label</h4></div><div class="card-body">@include('admin.labels.form',['label'=>null])</div></div>@endsection
