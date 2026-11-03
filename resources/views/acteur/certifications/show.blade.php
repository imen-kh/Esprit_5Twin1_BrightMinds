@extends('layouts.front')
@section('title','Certification')
@section('content')
<div class="container py-5"><div class="card p-4">
    <div class="d-flex justify-content-between"><h2>{{ $certification->product?->name ?? 'Certification' }}</h2><x-certification-badge :certification="$certification" /></div>
    <p>Label : {{ $certification->label?->nom }}</p><p>Numéro : {{ $certification->numero }}</p><p>Expiration : {{ optional($certification->date_expiration)->format('d/m/Y') }}</p>
    <div class="row align-items-center mt-4"><div class="col-md-8">@if($certification->fichier_pdf)<a href="{{ asset('storage/'.$certification->fichier_pdf) }}" target="_blank" class="btn btn-outline-primary">Voir le document</a>@endif
    <form method="POST" action="{{ route('acteur.certifications.analyser', $certification) }}" class="mt-3">@csrf<button class="btn btn-outline-info">Analyser avec l’IA locale</button></form>
    @if(session('analyse_ia')) @include('certifications._analysis', ['analyse' => session('analyse_ia')]) @endif</div><div class="col-md-4 text-center"><div class="small text-muted mb-2">Scanner pour vérifier</div><img src="{{ route('certifications.public.qr', $certification) }}" width="180" height="180" alt="QR code de vérification"><div class="small text-muted mt-2">La page scannée est publique.</div></div></div>
</div></div>
@endsection
