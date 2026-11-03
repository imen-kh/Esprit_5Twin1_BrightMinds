{{-- Agent Sahbi (module Réclamations & Avis) : widget flottant pour les utilisateurs connectés --}}
@auth
@if (auth()->user()->hasVerifiedEmail())
    @php
        $utilisateurSahbi = auth()->user();
        // Accroche proactive : un produit reçu pas encore noté
        $aNoterSahbi = $utilisateurSahbi->isAdmin() || $utilisateurSahbi->isActeur()
            ? null
            : app(\App\Services\Assistant\AssistantOutils::class)->produitsNonEvalues($utilisateurSahbi)->first();
    @endphp
    <link rel="stylesheet" href="{{ asset('css/sahbi.css') }}">
    <div id="sahbi" class="sahbi {{ ($contexte ?? 'front') === 'back' ? 'sahbi-back' : '' }}"
         data-etat="{{ route('assistant.etat') }}"
         data-message="{{ route('assistant.message') }}"
         data-decision="{{ route('assistant.decider', ['message' => '__ID__']) }}"
         data-reset="{{ route('assistant.reinitialiser') }}"
         data-token="{{ csrf_token() }}">

        @if ($aNoterSahbi)
            <div class="sahbi-bulle-accroche" hidden>Comment avez-vous trouvé <strong>{{ $aNoterSahbi->name }}</strong> ? Dites-le à Sahbi 🍅</div>
        @endif

        <section class="sahbi-panneau" hidden role="dialog" aria-label="Assistant Sahbi">
            <header class="sahbi-entete">
                <span class="sahbi-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="11" rx="4"/><path d="M12 8V4"/><circle cx="12" cy="3.5" r="1"/><circle cx="9" cy="13.5" r="1.2" fill="currentColor"/><circle cx="15" cy="13.5" r="1.2" fill="currentColor"/><path d="M9.5 16.5c1.4.9 3.6.9 5 0"/></svg>
                </span>
                <div>
                    <div class="sahbi-titre">Sahbi</div>
                    <div class="sahbi-statut">Connexion…</div>
                </div>
                <button type="button" class="sahbi-nouveau" title="Nouvelle conversation" aria-label="Nouvelle conversation">↻</button>
                <button type="button" class="sahbi-fermer" title="Fermer" aria-label="Fermer">✕</button>
            </header>
            <div class="sahbi-fil" aria-live="polite"></div>
            <div class="sahbi-suggestions" hidden></div>
            <div class="sahbi-saisie">
                <textarea rows="1" maxlength="1000" placeholder="Écrivez à Sahbi… (FR, عربي, EN, derja)" aria-label="Votre message"></textarea>
                <button type="button" aria-label="Envoyer">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.4 21 12 3.4 3.6 3 10l12 2-12 2z"/></svg>
                </button>
            </div>
            <div class="sahbi-mention">Sahbi propose, vous confirmez : rien n'est enregistré sans votre accord.</div>
        </section>

        <button type="button" class="sahbi-lanceur" aria-expanded="false" aria-label="Ouvrir l'assistant Sahbi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-7l-5 4v-4H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/><circle cx="9" cy="11" r="1" fill="currentColor"/><circle cx="15" cy="11" r="1" fill="currentColor"/></svg>
            @if ($aNoterSahbi)<span class="sahbi-pastille" aria-hidden="true"></span>@endif
        </button>
    </div>
    <script src="{{ asset('js/sahbi.js') }}" defer></script>
@endif
@endauth
