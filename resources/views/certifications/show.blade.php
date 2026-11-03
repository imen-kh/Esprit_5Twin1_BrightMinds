@extends('layouts.front')

@section('title', 'Certification vérifiable')

@section('content')
<div class="container py-5">
    <div class="card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge bg-success mb-2">Certification vérifiable</span>
                <h1 class="h3">{{ $certification->product?->name ?? 'Produit' }}</h1>
                <p class="text-muted mb-0">{{ $certification->label?->nom ?? 'Label non renseigné' }}</p>
            </div>
            <x-certification-badge :certification="$certification" />
        </div>

        <dl class="row mt-4 mb-0">
            <dt class="col-sm-4">Numéro</dt>
            <dd class="col-sm-8">{{ $certification->numero ?: 'Non renseigné' }}</dd>
            <dt class="col-sm-4">Organisme</dt>
            <dd class="col-sm-8">{{ $certification->label?->organisme ?: 'Non renseigné' }}</dd>
            <dt class="col-sm-4">Producteur</dt>
            <dd class="col-sm-8">{{ $certification->acteur?->nom_entreprise ?: ($certification->acteur?->name ?: 'Non renseigné') }}</dd>
            <dt class="col-sm-4">Date d’obtention</dt>
            <dd class="col-sm-8">{{ optional($certification->date_obtention)->format('d/m/Y') ?: 'Non renseignée' }}</dd>
            <dt class="col-sm-4">Date d’expiration</dt>
            <dd class="col-sm-8">{{ optional($certification->date_expiration)->format('d/m/Y') ?: 'Sans expiration' }}</dd>
            <dt class="col-sm-4">Statut</dt>
            <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $certification->statut)) }}</dd>
        </dl>

        <div class="row align-items-center mt-4">
            <div class="col-md-8">
                @if($certification->fichier_pdf)
                    <a href="{{ asset('storage/'.$certification->fichier_pdf) }}" target="_blank" class="btn btn-outline-primary">Voir le justificatif</a>
                @endif
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimer</button>
            </div>
            <div class="col-md-4 text-center">
                <img src="{{ route('certifications.public.qr', $certification) }}" width="190" height="190" alt="QR code de vérification de la certification">
                <div class="small text-muted">Scannez pour vérifier cette certification</div>
            </div>
        </div>
    </div>
</div>
@endsection
