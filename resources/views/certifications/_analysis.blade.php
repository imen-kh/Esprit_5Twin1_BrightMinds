<div class="card border-info mt-4">
    <div class="card-header bg-info-subtle d-flex justify-content-between align-items-center">
        <strong>Audit IA local et explicable</strong>
        <span class="badge text-bg-{{ $analyse['score'] >= 80 ? 'success' : ($analyse['score'] >= 55 ? 'warning' : 'danger') }}">{{ $analyse['score'] }}/100 · {{ $analyse['niveau'] }}</span>
    </div>
    <div class="card-body">
        <p>{{ $analyse['resume'] }}</p>
        @if($analyse['points_forts'])
            <h6>Signaux positifs</h6>
            <ul>@foreach($analyse['points_forts'] as $point)<li>{{ $point }}</li>@endforeach</ul>
        @endif
        @if($analyse['recommandations'])
            <h6>Recommandations</h6>
            <ul class="mb-0">@foreach($analyse['recommandations'] as $recommendation)<li>{{ $recommendation }}</li>@endforeach</ul>
        @endif
    </div>
</div>
