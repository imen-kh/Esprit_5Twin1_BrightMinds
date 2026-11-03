<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dossier {{ $reclamation->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2b2f33; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #5f9005; border-bottom: 2px solid #81c408; padding-bottom: 3px; margin: 18px 0 8px; }
        .entete { border-bottom: 3px solid #81c408; padding-bottom: 8px; margin-bottom: 12px; }
        .muted { color: #6c757d; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 8px; background: #eef5e1; color: #5f9005; font-size: 10px; margin-right: 4px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 6px; vertical-align: top; }
        .grille td { border: 1px solid #e9ecef; }
        .lib { width: 28%; background: #f7faf2; font-weight: bold; }
        .message { border-left: 3px solid #81c408; padding: 6px 10px; margin-bottom: 8px; background: #fafafa; }
        .message.client { border-left-color: #ffb524; }
        .pied { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 9px; color: #6c757d; text-align: center; }
    </style>
</head>
<body>
    <div class="entete">
        <h1>NutriTrace — Dossier de réclamation {{ $reclamation->reference }}</h1>
        <div class="muted">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>

    <div>
        <span class="badge">{{ $reclamation->statutLibelle() }}</span>
        <span class="badge">Priorité {{ mb_strtolower($reclamation->prioriteLibelle()) }}</span>
        <span class="badge">{{ $reclamation->categorieLibelle() }}</span>
        <span class="badge">{{ $reclamation->slaLibelle() }}</span>
    </div>

    <h2>Réclamation</h2>
    <table class="grille">
        <tr><td class="lib">Sujet</td><td>{{ $reclamation->sujet }}</td></tr>
        <tr><td class="lib">Client</td><td>{{ $reclamation->user?->name }} ({{ $reclamation->user?->email }})</td></tr>
        <tr><td class="lib">Produit</td><td>{{ $reclamation->produit?->name }}</td></tr>
        <tr><td class="lib">Commande</td><td>{{ $reclamation->commande_id ? '#'.$reclamation->commande_id : 'Non renseignée' }}</td></tr>
        <tr><td class="lib">Lot</td><td>{{ $reclamation->lot?->code ?? 'Non renseigné' }}</td></tr>
        <tr><td class="lib">Déposée le</td><td>{{ $reclamation->created_at?->format('d/m/Y H:i') }} ({{ $reclamation->sourceLibelle() }})</td></tr>
        <tr><td class="lib">Échéance</td><td>{{ $reclamation->echeance_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="lib">Description</td><td>{!! nl2br(e($reclamation->description)) !!}</td></tr>
    </table>

    <h2>Analyse IA</h2>
    <table class="grille">
        <tr><td class="lib">Résumé</td><td>{{ $reclamation->resume_ia ?? '—' }}</td></tr>
        <tr><td class="lib">Sentiment</td><td>{{ ucfirst((string) $reclamation->sentiment) ?: '—' }}</td></tr>
        <tr><td class="lib">Priorité suggérée</td><td>{{ \App\Models\Reclamation::prioritesLibelles()[$reclamation->priorite_suggeree] ?? '—' }}</td></tr>
        <tr><td class="lib">Maillon probable</td><td>{{ $reclamation->responsableLibelle() ?? 'Non analysé' }}@if ($reclamation->responsable_explication) — {{ $reclamation->responsable_explication }}@endif</td></tr>
        @if ($reclamation->satisfaction_note)
            <tr><td class="lib">Satisfaction client</td><td>{{ $reclamation->satisfaction_note }}/5 {{ $reclamation->satisfaction_commentaire ? '— '.$reclamation->satisfaction_commentaire : '' }}</td></tr>
        @endif
    </table>

    <h2>Échanges ({{ $reclamation->reponses->count() }})</h2>
    @forelse ($reclamation->reponses as $reponse)
        <div class="message {{ $reponse->user_id === $reclamation->user_id ? 'client' : '' }}">
            <div class="muted"><strong>{{ $reponse->user?->name }}</strong> — {{ $reponse->created_at?->format('d/m/Y H:i') }}</div>
            {!! nl2br(e($reponse->message)) !!}
        </div>
    @empty
        <p class="muted">Aucun échange.</p>
    @endforelse

    <h2>Chronologie</h2>
    <table class="grille">
        @foreach ($reclamation->historique as $evenement)
            <tr><td class="lib" style="width: 22%">{{ $evenement->created_at?->format('d/m/Y H:i') }}</td><td>{{ $evenement->libelle }}@if ($evenement->user) <span class="muted">— {{ $evenement->user->name }}</span>@endif</td></tr>
        @endforeach
    </table>

    <div class="pied">NutriTrace · Module Réclamations &amp; Avis · {{ $reclamation->reference }}</div>
</body>
</html>
