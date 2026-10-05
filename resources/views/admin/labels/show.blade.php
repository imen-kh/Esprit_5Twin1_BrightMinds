@extends('layouts.back')
@section('title',$label->nom)
@section('content')<div class="card"><div class="card-body"><h3>{{ $label->nom }}</h3><p>{{ $label->description }}</p><a href="{{ route('admin.labels.edit',$label) }}" class="btn btn-primary">Modifier</a></div></div>@endsection
