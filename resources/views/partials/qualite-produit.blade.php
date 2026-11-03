@php
    $evaluable = (bool) ($analyse['evaluable'] ?? false);
    $classe = match ($analyse['classification'] ?? null) {
        'Excellente' => 'bg-success',
        'Bonne' => 'bg-primary',
        'Moyenne' => 'bg-warning text-dark',
        'Faible' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="h5 mb-1">Qualité visuelle</h3>
                <p class="small text-body-secondary mb-0">{{ $analyse['avertissement'] ?? 'Indication visuelle seulement.' }}</p>
            </div>
            @if ($evaluable)
                <div class="text-center flex-shrink-0">
                    <div class="display-6 fw-bold text-primary lh-1">{{ $analyse['score'] }}</div>
                    <div class="small text-body-secondary">/ 100</div>
                    <span class="badge {{ $classe }} mt-1">{{ $analyse['classification'] }}</span>
                </div>
            @else
                <span class="badge bg-secondary">Photo insuffisante</span>
            @endif
        </div>
        @if ($evaluable)
            <p class="fw-medium mb-1">Défauts visibles</p>
            @if (! empty($analyse['defauts']))
                <ul class="mb-3">
                    @foreach ($analyse['defauts'] as $defaut)
                        <li>{{ $defaut }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-body-secondary">Aucun défaut visible sur cette photo.</p>
            @endif
        @endif
        @if (! empty($analyse['recommandations']))
            <p class="fw-medium mb-1">Tri et présentation</p>
            <ul class="mb-0">
                @foreach ($analyse['recommandations'] as $recommandation)
                    <li>{{ $recommandation }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
