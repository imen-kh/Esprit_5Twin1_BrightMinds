@extends('layouts.front')

@section('title', $reclamation->reference.' — '.$reclamation->sujet)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
@include('partials.front.page-header', ['titre' => 'Réclamation '.$reclamation->reference])

@php $estClient = $reclamation->user_id === auth()->id(); @endphp

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ $estClient ? route('reclamations.index') : route('acteur.reclamations.index') }}" class="text-primary small"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <h2 class="h3 mt-2 mb-1">{{ $reclamation->sujet }}</h2>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="ra-reference text-muted">{{ $reclamation->reference }}</span>
                <span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span>
                <span class="badge {{ $reclamation->prioriteBadge() }}">Priorité {{ mb_strtolower($reclamation->prioriteLibelle()) }}</span>
                <span class="badge {{ $reclamation->slaBadge() }}"><i class="far fa-clock me-1"></i>{{ $reclamation->slaLibelle() }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('update', $reclamation)
                <a href="{{ route('reclamations.edit', $reclamation) }}" class="btn btn-outline-primary rounded-pill">Modifier</a>
                <form action="{{ route('reclamations.destroy', $reclamation) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill">Supprimer</button>
                </form>
            @endcan
            @if ($estClient && $reclamation->estEnRetard())
                <form method="POST" action="{{ route('reclamations.relancer', $reclamation) }}">
                    @csrf
                    <button class="btn btn-secondary text-white rounded-pill"><i class="fas fa-bullhorn me-2"></i>Relancer le service client</button>
                </form>
            @endif
            @can('cloturer', $reclamation)
                <form method="POST" action="{{ route('reclamations.cloturer', $reclamation) }}" onsubmit="return confirm('Confirmez-vous que votre problème est réglé ?');">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-primary text-white rounded-pill"><i class="fas fa-check me-2"></i>Mon problème est réglé</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Enquête de satisfaction après clôture --}}
            @can('evaluer', $reclamation)
                <form method="POST" action="{{ route('reclamations.evaluer', $reclamation) }}" class="ra-bandeau-conversion p-4 mb-4">
                    @csrf
                    <h5 class="mb-1">Comment s'est passé le traitement de votre réclamation ?</h5>
                    <p class="text-muted small">Votre note nous aide à améliorer le service client.</p>
                    <div class="ra-choix-etoiles mb-2" role="radiogroup" aria-label="Satisfaction sur 5">
                        @for ($n = 5; $n >= 1; $n--)
                            <input type="radio" id="satisfaction-{{ $n }}" name="satisfaction_note" value="{{ $n }}" required>
                            <label for="satisfaction-{{ $n }}" title="{{ $n }}/5"><i class="fas fa-star"></i></label>
                        @endfor
                    </div>
                    @error('satisfaction_note')<div class="text-danger small">{{ $message }}</div>@enderror
                    <textarea name="satisfaction_commentaire" rows="2" class="form-control mb-3" maxlength="1000" placeholder="Un commentaire ? (facultatif)"></textarea>
                    <button class="btn btn-secondary text-white rounded-pill px-4">Envoyer mon évaluation</button>
                </form>
            @elseif ($reclamation->satisfaction_note)
                <div class="ra-carte p-3 mb-4 d-flex align-items-center gap-3">
                    <i class="fas fa-heart text-secondary fa-lg"></i>
                    <div>Traitement évalué <x-etoiles :note="$reclamation->satisfaction_note" />@if ($reclamation->satisfaction_commentaire) — « {{ $reclamation->satisfaction_commentaire }} »@endif</div>
                </div>
            @endif

            <div class="ra-carte p-4 mb-4">
                <div class="row g-3 small mb-3">
                    <div class="col-sm-6"><span class="text-muted">Produit</span><br><strong>{{ $reclamation->produit?->name ?? '—' }}</strong></div>
                    <div class="col-sm-6"><span class="text-muted">Catégorie</span><br><strong><i class="fas {{ $reclamation->categorieIconeFront() }} text-primary me-1"></i>{{ $reclamation->categorieLibelle() }}</strong></div>
                    <div class="col-sm-6"><span class="text-muted">Commande</span><br><strong>{{ $reclamation->commande_id ? '#'.$reclamation->commande_id : 'Non renseignée' }}</strong></div>
                    <div class="col-sm-6"><span class="text-muted">Lot</span><br>
                        @if ($reclamation->lot)
                            <a href="{{ route('tracabilite.show', $reclamation->lot) }}"><strong>{{ $reclamation->lot->code }}</strong></a>
                        @else
                            <strong>Non renseigné</strong>
                        @endif
                    </div>
                </div>
                <p class="mb-0" style="white-space: pre-line;">{{ $reclamation->description }}</p>
            </div>

            <h3 class="h5 mb-3">Échanges</h3>
            <div class="ra-fil mb-4">
                @forelse ($reclamation->reponses as $reponse)
                    @php $deClient = $reponse->user_id === $reclamation->user_id; @endphp
                    <div class="ra-bulle {{ $deClient ? 'client' : 'equipe' }}">
                        <div class="entete">
                            <strong>{{ $deClient ? ($reponse->user?->name ?? 'Client') : ($reponse->user?->isAdmin() ? 'Service client NutriTrace' : ($reponse->user?->name ?? 'Producteur').' (producteur)') }}</strong>
                            · {{ $reponse->created_at?->format('d/m/Y H:i') }}
                        </div>
                        {{ $reponse->message }}
                    </div>
                @empty
                    <p class="text-muted">Aucune réponse pour le moment. Notre équipe vous répondra {{ $reclamation->echeance_at ? 'avant le '.$reclamation->echeance_at->format('d/m à H\hi') : 'rapidement' }}.</p>
                @endforelse
            </div>

            @can('repondre', $reclamation)
                <form class="ra-carte p-4" method="POST" action="{{ route('reclamations.reponses.store', $reclamation) }}">
                    @csrf
                    <label class="form-label fw-bold" for="message">{{ $estClient ? 'Ajouter une précision' : 'Répondre au client' }}</label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="3" required maxlength="5000">{{ old('message') }}</textarea>
                    <div class="invalid-feedback">@error('message'){{ $message }}@enderror</div>
                    <button class="btn btn-primary text-white rounded-pill mt-3" type="submit"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
                </form>
            @elseif ($reclamation->estCloturee())
                <p class="text-muted small"><i class="fas fa-lock me-1"></i>Ce dossier est clôturé : les échanges sont fermés.</p>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="ra-carte p-4">
                <h3 class="h6 mb-3"><i class="fas fa-stream text-primary me-2"></i>Suivi du dossier</h3>
                <ul class="ra-chrono">
                    @foreach ($reclamation->historique as $evenement)
                        <li>
                            <span class="point {{ $evenement->couleur() }}"><i class="fas {{ $evenement->iconeFront() }}"></i></span>
                            <div class="small fw-semibold">{{ $evenement->libelle }}</div>
                            <div class="small text-muted">{{ $evenement->created_at?->format('d/m/Y H:i') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
