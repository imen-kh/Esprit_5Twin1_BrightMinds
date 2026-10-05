@props(['certification'])
@php($status = $certification->statut)
<span {{ $attributes->merge(['class' => 'badge '.($status === 'validee' ? 'bg-success' : ($status === 'refusee' ? 'bg-danger' : 'bg-warning text-dark'))]) }}>
    {{ $status === 'validee' ? 'Vérifiée' : ($status === 'refusee' ? 'Refusée' : 'En attente') }}
    @if ($certification->is_expired) · Expirée @endif
</span>
