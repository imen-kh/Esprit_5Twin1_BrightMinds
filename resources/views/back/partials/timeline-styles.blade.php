{{-- Styles de la timeline des étapes (le template Sneat gratuit n'inclut pas ce composant) --}}
@push('styles')
  <style>
    .timeline-etapes { list-style: none; margin: 0; padding: 0 0 0 0.5rem; }
    .timeline-etapes .timeline-item { position: relative; padding: 0 0 1.75rem 2.75rem; border-left: 2px solid var(--bs-border-color); }
    .timeline-etapes .timeline-item:last-child { border-left-color: transparent; padding-bottom: 0; }
    .timeline-etapes .timeline-point {
      position: absolute; left: -1.1rem; top: 0;
      width: 2.2rem; height: 2.2rem; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 0 0 4px var(--bs-card-bg, #fff);
    }
  </style>
@endpush
