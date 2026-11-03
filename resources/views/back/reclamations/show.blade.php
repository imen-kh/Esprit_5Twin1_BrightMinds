@extends('layouts.back')

@section('title', $reclamation->reference)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reclamations-avis.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="small text-muted mb-1"><span class="ra-reference">{{ $reclamation->reference }}</span> · {{ $reclamation->created_at?->format('d/m/Y H:i') }} · {{ $reclamation->sourceLibelle() }}</div>
        <h4 class="mb-2">{{ $reclamation->sujet }}</h4>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge {{ $reclamation->statutBadge() }}">{{ $reclamation->statutLibelle() }}</span>
            <span class="badge {{ $reclamation->prioriteBadge() }}">Priorité {{ mb_strtolower($reclamation->prioriteLibelle()) }}</span>
            <span class="badge {{ $reclamation->slaBadge() }}"><i class="bx bx-time-five me-1"></i>{{ $reclamation->slaLibelle() }}</span>
            @if ($reclamation->satisfaction_note)
                <span class="badge bg-label-warning"><i class="bx bxs-star me-1"></i>Satisfaction {{ $reclamation->satisfaction_note }}/5</span>
            @endif
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reclamations.index') }}" class="btn btn-outline-secondary">Retour</a>
        <a href="{{ route('admin.reclamations.pdf', $reclamation) }}" class="btn btn-outline-dark"><i class="bx bxs-file-pdf me-1"></i>Dossier PDF</a>
        <a href="{{ route('admin.reclamations.edit', $reclamation) }}" class="btn btn-primary">Modifier</a>
        <form action="{{ route('admin.reclamations.destroy', $reclamation) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Supprimer</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 small mb-3">
                    <div class="col-sm-4"><span class="text-muted d-block">Consommateur</span><strong>{{ $reclamation->user?->name ?? '—' }}</strong><div class="text-muted">{{ $reclamation->user?->email }}</div></div>
                    <div class="col-sm-4"><span class="text-muted d-block">Produit</span><strong>{{ $reclamation->produit?->name ?? '—' }}</strong><div class="text-muted">{{ $reclamation->produit?->user?->name }}</div></div>
                    <div class="col-sm-4"><span class="text-muted d-block">Commande / lot</span>
                        <strong>{{ $reclamation->commande_id ? '#'.$reclamation->commande_id : 'Sans commande' }}</strong>
                        @if ($reclamation->commande)<div class="text-muted">{{ number_format((float) $reclamation->commande->total, 3, ',', ' ') }} DT · {{ $reclamation->commande->statut }}</div>@endif
                        @if ($reclamation->lot)<div><a href="{{ route('tracabilite.show', $reclamation->lot) }}" target="_blank">{{ $reclamation->lot->code }}</a> <span class="badge bg-label-{{ \App\Models\Lot::STATUTS[$reclamation->lot->statut]['badge'] ?? 'secondary' }}">{{ \App\Models\Lot::STATUTS[$reclamation->lot->statut]['label'] ?? $reclamation->lot->statut }}</span></div>@endif
                    </div>
                </div>
                <p class="mb-0" style="white-space: pre-line;">{{ $reclamation->description }}</p>
                @if ($reclamation->avis)
                    <div class="alert alert-warning small mt-3 mb-0"><i class="bx bxs-star me-1"></i>Ouverte depuis un avis {{ $reclamation->avis->note }}★ publié le {{ $reclamation->avis->created_at?->format('d/m/Y') }}.</div>
                @endif
            </div>
        </div>

        {{-- Analyse IA --}}
        <div class="card mb-4 ra-carte-ia border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                <h5 class="mb-0"><i class="bx bx-bot me-1"></i>Analyse IA</h5>
                <form method="POST" action="{{ route('admin.reclamations.reanalyser', $reclamation) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary"><i class="bx bx-refresh me-1"></i>Relancer l'analyse</button>
                </form>
            </div>
            <div class="card-body">
                @if ($reclamation->resume_ia)
                    <p class="mb-3"><strong>Résumé :</strong> {{ $reclamation->resume_ia }}</p>
                @endif
                <div class="row g-3 small">
                    <div class="col-6 col-md-3"><span class="text-muted d-block">Catégorie</span><strong><i class="bx {{ $reclamation->categorieIcone() }} me-1"></i>{{ $reclamation->categorieLibelle() }}</strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted d-block">Sentiment</span><strong>{{ ucfirst((string) $reclamation->sentiment ?: '—') }}</strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted d-block">Priorité suggérée</span><strong>{{ \App\Models\Reclamation::prioritesLibelles()[$reclamation->priorite_suggeree] ?? '—' }}</strong></div>
                    <div class="col-6 col-md-3"><span class="text-muted d-block">Langue</span><strong>{{ \App\Services\AiReclamationService::LANGUES[$reclamation->langue] ?? 'Français' }}</strong></div>
                </div>

                <hr>
                {{-- « Qui est responsable ? » --}}
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h6 class="mb-1"><i class="bx bx-git-branch me-1"></i>Qui est responsable ?</h6>
                        @if ($reclamation->responsable_suggere)
                            <p class="mb-1"><span class="badge bg-warning text-dark">{{ $reclamation->responsableLibelle() }}</span></p>
                            <p class="small mb-0">{{ $reclamation->responsable_explication }}</p>
                        @else
                            <p class="small text-muted mb-0">L’IA croise la réclamation avec les étapes de traçabilité du lot (températures, délais, acteurs) pour désigner le maillon probablement en cause.</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.reclamations.responsabilite', $reclamation) }}">
                        @csrf
                        <button class="btn btn-sm btn-warning">{{ $reclamation->responsable_suggere ? 'Réanalyser' : 'Analyser la chaîne' }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Échanges et copilote --}}
        <div class="card mb-4" id="reponse">
            <div class="card-header"><h5 class="mb-0">Échanges</h5></div>
            <div class="card-body">
                <div class="ra-fil mb-4">
                    @forelse ($reclamation->reponses as $reponse)
                        @php $deClient = $reponse->user_id === $reclamation->user_id; @endphp
                        <div class="ra-bulle {{ $deClient ? 'equipe' : 'client' }}">
                            <div class="entete d-flex justify-content-between gap-3">
                                <span><strong>{{ $reponse->user?->name ?? 'Utilisateur' }}</strong>{{ $deClient ? ' (client)' : ($reponse->user?->isAdmin() ? ' (service client)' : ' (producteur)') }}</span>
                                <span>{{ $reponse->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="corps">{{ $reponse->message }}</div>
                            @if ($reponse->user_id === auth()->id())
                                <details class="mt-2 small">
                                    <summary class="text-muted">Modifier / supprimer</summary>
                                    <form class="mt-2" method="POST" action="{{ route('admin.reponses-reclamations.update', $reponse) }}">
                                        @csrf
                                        @method('PUT')
                                        <textarea class="form-control form-control-sm mb-2" name="message" rows="3" required>{{ $reponse->message }}</textarea>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Modifier</button>
                                    </form>
                                    <form class="mt-2" method="POST" action="{{ route('admin.reponses-reclamations.destroy', $reponse) }}" onsubmit="return confirm('Supprimer cette réponse ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucune réponse.</p>
                    @endforelse
                </div>

                @if (! $reclamation->estCloturee())
                    <div class="ra-carte-ia p-3 mb-3" id="copilote" data-url="{{ route('admin.reclamations.copilote', $reclamation) }}" data-token="{{ csrf_token() }}">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="ra-badge-ia"><i class="bx bx-bot"></i>Copilote</span>
                            <span class="small text-muted me-auto">Brouillon dans la langue du client, avec une solution adaptée.</span>
                            <select class="form-select form-select-sm w-auto" id="copilote-ton" aria-label="Ton de la réponse">
                                @foreach ($tons as $valeur => $libelle)
                                    <option value="{{ $valeur }}">{{ $libelle }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-primary" id="copilote-generer"><i class="bx bx-edit-alt me-1"></i>Générer</button>
                        </div>
                        <div class="small mt-2 d-none" id="copilote-info"></div>
                    </div>

                    <form method="POST" action="{{ route('admin.reclamations.reponses.store', $reclamation) }}">
                        @csrf
                        <label class="form-label" for="message">Répondre au client</label>
                        <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($reclamation->suggestion_reponse)
                            <button type="button" class="btn btn-sm btn-link px-0" onclick="document.getElementById('message').value = @js($reclamation->suggestion_reponse)">Utiliser la suggestion initiale de l'IA</button>
                        @endif
                        <div><button class="btn btn-primary mt-2" type="submit"><i class="bx bx-send me-1"></i>Envoyer la réponse</button></div>
                    </form>
                @else
                    <p class="small text-muted mb-0"><i class="bx bx-lock-alt me-1"></i>Dossier clôturé.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Statut</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.reclamations.statut', $reclamation) }}">
                    @csrf
                    @method('PATCH')
                    <select class="form-select mb-3" name="statut">
                        @foreach (\App\Models\Reclamation::statutsLibelles() as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected($reclamation->statut === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary w-100" type="submit">Mettre à jour le statut</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Échéance : {{ $reclamation->echeance_at?->format('d/m/Y H:i') ?? '—' }} ({{ config('reclamations.sla_heures.'.$reclamation->priorite) }} h pour la priorité {{ mb_strtolower($reclamation->prioriteLibelle()) }}).</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Chronologie</h5></div>
            <div class="card-body">
                <ul class="ra-chrono">
                    @foreach ($reclamation->historique as $evenement)
                        <li>
                            <span class="point {{ $evenement->couleur() }}"><i class="bx {{ $evenement->icone() }}"></i></span>
                            <div class="small fw-semibold">{{ $evenement->libelle }}</div>
                            <div class="small text-muted">{{ $evenement->created_at?->format('d/m H:i') }}@if ($evenement->user) · {{ $evenement->user->name }}@endif</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if ($autres->isNotEmpty())
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Sur le même produit</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach ($autres as $autre)
                        <li class="list-group-item">
                            <a href="{{ route('admin.reclamations.show', $autre) }}">{{ \Illuminate\Support\Str::limit($autre->sujet, 40) }}</a>
                            <div class="small text-muted">{{ $autre->reference }} · {{ $autre->categorieLibelle() }} · <span class="badge {{ $autre->statutBadge() }}">{{ $autre->statutLibelle() }}</span></div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Copilote : demande un brouillon à l'IA et le place dans la zone de réponse, à relire avant l'envoi
(() => {
    const bloc = document.getElementById('copilote');
    if (!bloc) return;
    const bouton = document.getElementById('copilote-generer');
    const info = document.getElementById('copilote-info');
    const zone = document.getElementById('message');

    bouton.addEventListener('click', async () => {
        bouton.disabled = true;
        bouton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rédaction…';
        info.classList.add('d-none');
        try {
            const reponse = await fetch(bloc.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': bloc.dataset.token },
                body: JSON.stringify({ ton: document.getElementById('copilote-ton').value }),
            });
            const donnees = await reponse.json();
            if (!reponse.ok) throw new Error();
            zone.value = donnees.message;
            zone.focus();
            info.textContent = `Solution proposée : ${donnees.solution} · Langue : ${donnees.langue} · Source : ${donnees.source}. Relisez avant d'envoyer.`;
            info.classList.remove('d-none');
        } catch (e) {
            info.textContent = 'Le copilote est momentanément indisponible.';
            info.classList.remove('d-none');
        } finally {
            bouton.disabled = false;
            bouton.innerHTML = '<i class="bx bx-edit-alt me-1"></i>Générer';
        }
    });
})();
</script>
@endpush
