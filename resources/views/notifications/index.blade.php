@extends($layout)
@section('title', 'Mes notifications')

@push('styles')
<style>
.notif-card {
    transition: background .15s, box-shadow .15s;
}
.notif-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.notif-card.non-lue {
    border-left: 3px solid var(--bs-primary, #696cff);
    background-color: #f8f9ff;
}
.notif-card .notif-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-badge-type {
    font-size: .7rem;
    padding: .25em .55em;
    border-radius: 50px;
}
</style>
@endpush

@section('content')

@if ($layout === 'layouts.front')
{{-- espace de compensation pour la navbar fixe du front --}}
<div style="margin-top: 130px;"></div>
@endif

<div class="container py-5">
    {{-- En-tête --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0 fw-bold">
                <i class="{{ $layout === 'layouts.back' ? 'bx bx-bell me-2' : 'fas fa-bell me-2' }} text-primary"></i>
                Mes notifications
            </h1>
            <p class="text-muted mb-0 mt-1" style="font-size:.9rem;">
                Historique complet de vos notifications
            </p>
        </div>

        @if (auth()->user()->unreadNotifications->count() > 0)
            <form method="POST" action="{{ route('notifications.tout-lire') }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="{{ $layout === 'layouts.back' ? 'bx bx-check-double me-1' : 'fas fa-check-double me-1' }}"></i>
                    Tout marquer comme lu
                </button>
            </form>
        @endif
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    {{-- Liste --}}
    @forelse ($notifications as $notif)
        @php
            $data    = $notif->data;
            $estLue  = !is_null($notif->read_at);
            $type    = $data['type'] ?? '';
            $isLivraison = str_starts_with($type, 'livraison');
        @endphp

        <div class="card mb-2 border notif-card {{ $estLue ? '' : 'non-lue' }}">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-start gap-3">

                    {{-- Icône --}}
                    <div class="notif-icon bg-{{ $estLue ? 'light' : 'primary bg-opacity-10' }}">
                        @if ($layout === 'layouts.back')
                            <i class="bx {{ $isLivraison ? 'bx-package' : 'bx-cart' }} {{ $estLue ? 'text-muted' : 'text-primary' }}" style="font-size:1.2rem;"></i>
                        @else
                            <i class="fas {{ $isLivraison ? 'fa-shipping-fast' : 'fa-shopping-bag' }} {{ $estLue ? 'text-muted' : 'text-primary' }}"></i>
                        @endif
                    </div>

                    {{-- Contenu --}}
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                            <span class="fw-semibold {{ $estLue ? 'text-muted' : '' }}">
                                {{ $data['titre'] ?? 'Notification' }}
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                {{-- Badge type --}}
                                <span class="badge notif-badge-type {{ $isLivraison ? 'bg-info text-dark' : 'bg-secondary' }}">
                                    {{ $isLivraison ? 'Livraison' : 'Commande' }}
                                </span>
                                {{-- Badge lu/non-lu --}}
                                @if (!$estLue)
                                    <span class="badge bg-danger" style="font-size:.65rem;">Nouvelle</span>
                                @endif
                                {{-- Date --}}
                                <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-1 flex-wrap">
                            {{-- Lien vers la ressource --}}
                            @if (!empty($data['url']))
                                <a href="{{ $data['url'] }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:.8rem;">
                                    Voir le détail
                                </a>
                            @endif

                            {{-- Marquer comme lue --}}
                            @if (!$estLue)
                                <form method="POST" action="{{ route('notifications.lire', $notif->id) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-link p-0 text-muted" style="font-size:.8rem;">
                                        Marquer comme lue
                                    </button>
                                </form>
                            @else
                                <span class="text-muted" style="font-size:.8rem;">
                                    Lu {{ $notif->read_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

    @empty
        <div class="text-center py-5">
            <i class="{{ $layout === 'layouts.back' ? 'bx bx-bell-off' : 'fas fa-bell-slash' }} text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-0">Aucune notification pour le moment.</p>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($notifications->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@endsection
