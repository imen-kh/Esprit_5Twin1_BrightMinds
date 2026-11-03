@props(['note' => 0, 'taille' => null, 'jeu' => 'fa'])
{{-- Note sur 5 en étoiles (demi-étoile à partir de .5). jeu : « fa » au front (Font Awesome), « bx » au back (Boxicons) --}}
@php
    $note = max(0, min(5, (float) $note));
    $pleines = (int) floor($note);
    $demi = ($note - $pleines) >= 0.5;
    $icones = $jeu === 'bx'
        ? ['pleine' => 'bx bxs-star', 'demi' => 'bx bxs-star-half', 'vide' => 'bx bxs-star vide']
        : ['pleine' => 'fas fa-star', 'demi' => 'fas fa-star-half-alt', 'vide' => 'fas fa-star vide'];
@endphp
<span {{ $attributes->merge(['class' => 'ra-etoiles']) }} @if ($taille) style="font-size: {{ $taille }}" @endif
      role="img" aria-label="{{ str_replace('.', ',', (string) round($note, 1)) }} sur 5">
    @for ($i = 1; $i <= 5; $i++)
        <i class="{{ $i <= $pleines ? $icones['pleine'] : ($i === $pleines + 1 && $demi ? $icones['demi'] : $icones['vide']) }}"></i>
    @endfor
</span>
