<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de traçabilité {{ $lot->code }} | NutriTrace</title>
    {{-- Mise en page simple, compatible dompdf : CSS en ligne, police DejaVu Sans (accents), pas de flexbox --}}
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2b2b2b; margin: 0; }
        h1 { font-size: 20px; color: #81c408; margin: 0 0 2px; }
        h2 { font-size: 13px; color: #45595b; border-bottom: 2px solid #81c408; padding-bottom: 3px; margin: 16px 0 8px; }
        .entete { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; }
        .muted { color: #6c757d; }
        .infos { width: 100%; border-collapse: collapse; }
        .infos th { text-align: left; font-weight: normal; color: #6c757d; width: 30%; padding: 3px 0; }
        .infos td { padding: 3px 0; }
        .etapes { width: 100%; border-collapse: collapse; }
        .etapes th { background: #45595b; color: #fff; text-align: left; padding: 5px; font-size: 10px; }
        .etapes td { border-bottom: 1px solid #e3e3e3; padding: 5px; vertical-align: top; font-size: 10px; }
        .alerte { padding: 8px 10px; color: #fff; margin-bottom: 8px; border-radius: 4px; }
        .alerte-danger { background: #dc3545; }
        .alerte-warning { background: #e67e22; }
        .score { font-size: 26px; font-weight: bold; }
        .histoire { font-style: italic; background: #f4f6f8; border-left: 4px solid #81c408; padding: 8px 10px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; color: #fff; background: #45595b; }
        .pied { margin-top: 18px; font-size: 9px; color: #6c757d; text-align: center; }
        .barre-impression { text-align: center; margin: 12px; }
        @media print { .barre-impression { display: none; } }
    </style>
</head>
<body>
    @if ($impression)
        {{-- Version imprimable (dompdf non installé) : Ctrl+P > Enregistrer en PDF --}}
        <div class="barre-impression">
            <button type="button" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        </div>
    @endif

    <table class="entete">
        <tr>
            <td>
                <h1>NutriTrace</h1>
                <div class="muted">Fiche de traçabilité « de la ferme à l'assiette »</div>
                <h2 style="border: 0; margin-top: 10px;">{{ $lot->produit->name }} — lot {{ $lot->code }}</h2>
            </td>
            <td style="text-align: right; width: 140px;">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" width="110" height="110" alt="QR code du lot">
                <div class="muted" style="font-size: 8px;">Scannez pour la page en ligne</div>
            </td>
        </tr>
    </table>

    {{-- Alertes : rappel, péremption, chaîne du froid --}}
    @if ($lot->statut === 'rappele')
        <div class="alerte alerte-danger">
            <strong>LOT RAPPELÉ : ne consommez pas ce produit.</strong>
            @if ($lot->motif_rappel || $lot->notes)
                Motif : {{ $lot->motif_rappel ?: $lot->notes }}
            @endif
        </div>
    @endif
    @if ($alertePeremption)
        <div class="alerte alerte-{{ $alertePeremption['niveau'] }}"><strong>{{ $alertePeremption['titre'] }} :</strong> {{ $alertePeremption['message'] }}</div>
    @endif
    @foreach ($alertesFroid as $alerte)
        <div class="alerte alerte-danger"><strong>Rupture de la chaîne du froid :</strong> {{ $alerte['message'] }}</div>
    @endforeach

    <h2>Le produit et le lot</h2>
    <table class="infos">
        <tr><th>Produit</th><td>{{ $lot->produit->name }} ({{ $lot->produit->category?->name ?? 'sans catégorie' }})</td></tr>
        <tr><th>Producteur / propriétaire</th><td>{{ $lot->produit->user?->nom_entreprise ?: $lot->produit->user?->name }}</td></tr>
        @if ($lot->produit->origin)
            <tr><th>Origine annoncée</th><td>{{ $lot->produit->origin }}</td></tr>
        @endif
        <tr><th>Statut</th><td>{{ $lot->statut_label }}</td></tr>
        <tr><th>Production</th><td>{{ $lot->date_production->format('d/m/Y') }}</td></tr>
        <tr><th>Péremption</th><td>{{ $lot->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</td></tr>
        <tr><th>Quantité</th><td>{{ number_format($lot->quantite, 0, ',', ' ') }} {{ $lot->unite }}</td></tr>
        @if ($lot->lotOrigine)
            <tr><th>Matière première</th><td>lot {{ $lot->lotOrigine->code }} — {{ $lot->lotOrigine->produit->name }}</td></tr>
        @endif
    </table>

    <h2>Transparence</h2>
    <table class="infos">
        <tr>
            <th>Score de transparence</th>
            <td>
                <span class="score" style="color: {{ ['success' => '#198754', 'warning' => '#e67e22', 'danger' => '#dc3545'][\App\Models\Lot::couleurScore($score)] }}">{{ $score }}</span>/100
            </td>
        </tr>
        <tr><th>Parcours</th><td>{{ $stats['nombre_etapes'] }} étape(s), {{ $stats['duree_jours'] }} jour(s), {{ number_format($stats['distance_km'], 0, ',', ' ') }} km à vol d'oiseau</td></tr>
        <tr>
            <th>Contrôles automatiques</th>
            <td>
                @forelse ($anomalies as $anomalie)
                    <div>- {{ $anomalie['message'] }}</div>
                @empty
                    Aucune anomalie détectée (chaîne du froid, trajets, étapes, dates).
                @endforelse
            </td>
        </tr>
    </table>

    @if ($histoire)
        <h2>L'histoire du produit <span class="badge">Généré par IA</span></h2>
        <div class="histoire">{{ $histoire }}</div>
    @endif

    <h2>Les étapes, de la ferme à l'assiette</h2>
    @if ($chaine->isEmpty())
        <p class="muted">Aucune étape enregistrée pour ce lot.</p>
    @else
        <table class="etapes">
            <thead>
                <tr>
                    <th style="width: 18%;">Date</th>
                    <th style="width: 14%;">Étape</th>
                    <th>Description</th>
                    <th style="width: 16%;">Lieu</th>
                    <th style="width: 17%;">Acteur</th>
                    <th style="width: 8%;">Temp.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chaine as $etape)
                    <tr>
                        <td>{{ $etape->date_etape->format('d/m/Y H:i') }}</td>
                        <td>{{ $etape->type['label'] }}@if (! $etape->lot->is($lot))<br><span class="muted">lot {{ $etape->lot->code }}</span>@endif</td>
                        <td><strong>{{ $etape->titre }}</strong>@if ($etape->description)<br>{{ $etape->description }}@endif</td>
                        <td>{{ $etape->lieu }}</td>
                        <td>{{ $etape->acteur?->nom_entreprise ?: $etape->acteur?->name }}</td>
                        <td>{{ $etape->temperature_formatee ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="pied">
        Document généré le {{ $genereLe }} par NutriTrace — {{ route('tracabilite.show', $lot) }}
    </div>
</body>
</html>
