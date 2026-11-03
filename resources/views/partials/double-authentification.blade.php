{{--
  Double authentification par application (TOTP), partagée par les profils front et back.
  Paramètres : $user, $configurationDoubleAuth (QR + clé pendant l'activation, sinon null),
  $theme ('front' : Fruitables, 'back' : Sneat). Erreurs dans le sac « deuxFacteurs ».
--}}
@php
    $front = $theme === 'front';
    $classeChamp = fn (string $champ) => ($front ? 'form-control py-3 ' : 'form-control ')
        .($errors->deuxFacteurs->has($champ) ? 'is-invalid' : ($front ? 'border-0' : ''));
    $btnPrimaire = $front ? 'btn btn-primary rounded-pill text-white px-5 py-3' : 'btn btn-primary';
    $btnSecondaire = $front ? 'btn border-secondary rounded-pill text-primary px-5 py-3' : 'btn btn-outline-primary';
    $btnDanger = $front ? 'btn btn-danger rounded-pill px-5 py-3' : 'btn btn-danger';
    $btnLeger = $front ? 'btn btn-light rounded-pill px-4 py-2' : 'btn btn-sm btn-outline-secondary';
    $codes = session('codes_recuperation');
@endphp

<p class="mb-4">
    À chaque connexion, en plus du mot de passe, un code à 6 chiffres généré par une application
    d'authentification (Google Authenticator, Microsoft Authenticator…) vous sera demandé.
</p>

@if ($user->aDoubleAuthentification())
    {{-- ===== 2FA active : codes de récupération, régénération, désactivation ===== --}}
    @if ($codes)
        <div class="alert alert-warning" role="alert">
            <strong>Vos codes de récupération</strong> — ils ne seront plus affichés. Conservez-les en lieu sûr :
            chacun permet une connexion si vous perdez votre téléphone, une seule fois.
        </div>
        <div class="row g-2 mb-3" id="codes-recuperation">
            @foreach ($codes as $code)
                <div class="col-6 col-md-3"><code class="d-block text-center bg-white border rounded py-2 fs-6">{{ $code }}</code></div>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" class="{{ $btnLeger }}" id="copier-codes">Copier les codes</button>
            <button type="button" class="{{ $btnLeger }}" id="telecharger-codes">Télécharger (.txt)</button>
        </div>
    @else
        <p class="mb-4">
            Codes de récupération restants : <strong>{{ count($user->two_factor_recovery_codes ?? []) }}</strong> sur
            {{ \App\Services\DoubleAuthentificationService::NOMBRE_CODES_RECUPERATION }}.
        </p>
    @endif

    {{-- Un seul champ mot de passe pour les deux actions ; « Désactiver » envoie DELETE vers sa propre route --}}
    <form method="POST" action="{{ route('profil.two-factor.codes') }}" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-md-6">
                <label for="two_factor_password" class="form-label">Confirmez avec votre mot de passe<sup>*</sup></label>
                <input type="password" class="{{ $classeChamp('password') }}" id="two_factor_password" name="password" autocomplete="current-password">
                @error('password', 'deuxFacteurs')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="{{ $btnSecondaire }}">Régénérer les codes</button>
            <button
                type="submit"
                class="{{ $btnDanger }}"
                formaction="{{ route('profil.two-factor.desactiver') }}"
                name="_method"
                value="DELETE"
                onclick="return confirm('Désactiver la double authentification ? Votre compte ne sera plus protégé que par le mot de passe.')">
                Désactiver la 2FA
            </button>
        </div>
    </form>
@elseif ($configurationDoubleAuth)
    {{-- ===== Activation en cours : QR code, clé manuelle, premier code ===== --}}
    <div class="row g-4 align-items-center mb-4">
        <div class="col-md-auto text-center">
            <div class="d-inline-block bg-white border rounded p-3">{!! $configurationDoubleAuth['qr'] !!}</div>
        </div>
        <div class="col-md">
            <p class="mb-2"><strong>1.</strong> Scannez ce QR code avec votre application d'authentification.</p>
            <p class="mb-2"><strong>2.</strong> Ou saisissez cette clé manuellement :</p>
            <code class="d-inline-block bg-white border rounded px-3 py-2 fs-6 user-select-all">{{ $configurationDoubleAuth['cle'] }}</code>
            <p class="mt-3 mb-0"><strong>3.</strong> Saisissez le code à 6 chiffres affiché par l'application pour confirmer.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('profil.two-factor.confirmer') }}" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-md-6">
                <label for="two_factor_code" class="form-label">Code de l'application<sup>*</sup></label>
                <input
                    type="text"
                    class="{{ $classeChamp('code') }} text-center fs-5"
                    id="two_factor_code"
                    name="code"
                    placeholder="000000"
                    maxlength="7"
                    inputmode="numeric"
                    autocomplete="one-time-code">
                @error('code', 'deuxFacteurs')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="{{ $btnPrimaire }}">Confirmer et activer</button>
            <button type="submit" class="{{ $btnSecondaire }}" formaction="{{ route('profil.two-factor.annuler') }}" name="_method" value="DELETE">Annuler</button>
        </div>
    </form>
@else
    {{-- ===== 2FA inactive ===== --}}
    <form method="POST" action="{{ route('profil.two-factor.activer') }}" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-md-6">
                <label for="two_factor_password" class="form-label">Confirmez avec votre mot de passe<sup>*</sup></label>
                <input type="password" class="{{ $classeChamp('password') }}" id="two_factor_password" name="password" autocomplete="current-password">
                @error('password', 'deuxFacteurs')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <button class="{{ $btnPrimaire }} mt-4" type="submit">Activer la double authentification</button>
    </form>
@endif

@if ($codes)
    @push('scripts')
        <script>
            // Codes de récupération : copie dans le presse-papiers ou téléchargement en fichier texte
            document.addEventListener('DOMContentLoaded', function () {
                const codes = @json($codes);
                const texte = 'Codes de récupération NutriTrace ({{ $user->email }})\n'
                    + 'Chaque code ne fonctionne qu\'une seule fois.\n\n' + codes.join('\n') + '\n';

                document.getElementById('copier-codes')?.addEventListener('click', async function () {
                    try {
                        await navigator.clipboard.writeText(codes.join('\n'));
                        this.textContent = 'Codes copiés ✓';
                    } catch (erreur) {
                        this.textContent = 'Copie impossible : sélectionnez les codes';
                    }
                });

                document.getElementById('telecharger-codes')?.addEventListener('click', function () {
                    const lien = document.createElement('a');
                    lien.href = URL.createObjectURL(new Blob([texte], { type: 'text/plain' }));
                    lien.download = 'nutritrace-codes-recuperation.txt';
                    lien.click();
                    URL.revokeObjectURL(lien.href);
                });
            });
        </script>
    @endpush
@endif
