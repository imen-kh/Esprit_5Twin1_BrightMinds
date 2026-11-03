@extends('layouts.front')
@section('title', $label->nom)
@section('content')
<div class="container py-5"><div class="d-flex align-items-center gap-3 mb-4">
<img src="{{ $label->logo_url }}" style="width:90px;height:90px;object-fit:contain" alt="{{ $label->nom }}">
<div><h1>{{ $label->nom }}</h1><p class="text-muted mb-0">{{ $label->organisme }}</p></div></div>
<p>{{ $label->description }}</p><h3 class="mt-5">Certifications vérifiées</h3>
<div class="row g-3">@forelse($label->certifications as $certification)<div class="col-md-6"><div class="card p-3">
<div class="d-flex justify-content-between"><strong>{{ $certification->product?->nom ?? 'Produit' }}</strong><x-certification-badge :certification="$certification" /></div>
<small>Numéro : {{ $certification->numero }} · Expire le {{ optional($certification->date_expiration)->format('d/m/Y') }}</small><a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('certifications.public.show', $certification) }}">Vérifier la certification</a></div></div>
@empty <p>Aucune certification vérifiée pour ce label.</p>@endforelse</div></div>
@endsection
