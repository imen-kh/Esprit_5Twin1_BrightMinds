@extends('layouts.front')
@section('title', 'Labels certifiés')
@section('content')
<div class="container-fluid page-header py-5"><h1 class="text-center text-white display-6">Labels certifiés</h1></div>
<div class="container py-5"><div class="row g-4">
@forelse($labels as $label)
<div class="col-md-6 col-lg-4"><div class="card h-100 shadow-sm">
    <img src="{{ $label->logo_url }}" class="card-img-top p-4" style="height:180px;object-fit:contain" alt="{{ $label->nom }}">
    <div class="card-body"><h4>{{ $label->nom }}</h4><p class="text-muted">{{ $label->organisme }}</p><p>{{ Str::limit($label->description, 120) }}</p>
    <a class="btn btn-primary" href="{{ route('labels.show', $label) }}">Voir les certifications</a></div>
</div></div>
@empty <div class="col-12"><div class="alert alert-info">Aucun label actif.</div></div>@endforelse
</div><div class="mt-4">{{ $labels->links() }}</div></div>
@endsection
