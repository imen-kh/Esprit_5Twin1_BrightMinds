@extends('layouts.front')

@section('title', 'Mes récoltes')

@section('content')
@include('partials.front.page-header', ['titre' => 'Mes récoltes'])

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="mb-1 text-muted">La quantité estimée est celle que vous déclarez. La quantité récoltée n’apparaît qu’une fois la récolte terminée.</p>
            <p class="mb-0">Estimé : <strong>{{ \App\Models\Harvest::formaterKg($estime) }} kg</strong> · Récolté : <strong>{{ \App\Models\Harvest::formaterKg($recolte) }} kg</strong></p>
        </div>
        <a href="{{ route('acteur.recoltes.create') }}" class="btn btn-primary rounded-pill px-4">Planifier une récolte</a>
    </div>

    <div class="border rounded p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap gap-3 mb-3 small">
            <span><span class="badge" style="background:#ffb524;">&nbsp;</span> Planifiée</span>
            <span><span class="badge bg-success">&nbsp;</span> En cours</span>
            <span><span class="badge bg-secondary">&nbsp;</span> Récoltée</span>
        </div>
        <div id="calendrier-recoltes"></div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Plantation</th>
                    <th>Récolte prévue</th>
                    <th>Superficie</th>
                    <th>Estimée</th>
                    <th>Récoltée</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($recoltes as $recolte)
                <tr>
                    <td>{{ $recolte->nom() }}</td>
                    <td>{{ $recolte->planted_at->format('d/m/Y') }}</td>
                    <td>{{ $recolte->planned_harvest_at->format('d/m/Y') }}</td>
                    <td>{{ $recolte->area_hectares }} ha</td>
                    <td>{{ \App\Models\Harvest::formaterKg($recolte->estimated_kg) }} kg</td>
                    <td>{{ $recolte->status === \App\Models\Harvest::RECOLTEE ? \App\Models\Harvest::formaterKg($recolte->harvested_kg).' kg' : '—' }}</td>
                    <td><span class="badge {{ $recolte->classeStatut() }}">{{ $recolte->libelleStatut() }}</span></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('acteur.recoltes.show', $recolte) }}">Suivre</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('acteur.recoltes.edit', $recolte) }}">Modifier</a>
                        <form class="d-inline" method="POST" action="{{ route('acteur.recoltes.destroy', $recolte) }}" onsubmit="return confirm('Supprimer cette récolte ?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Aucune récolte planifiée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendrier = document.getElementById('calendrier-recoltes');
    if (!calendrier || typeof FullCalendar === 'undefined') {
        return;
    }
    new FullCalendar.Calendar(calendrier, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        height: 'auto',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
        events: @json(route('acteur.recoltes.evenements')),
        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location = info.event.url;
            }
        }
    }).render();
});
</script>
@endpush
