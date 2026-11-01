<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Double authentification par application (TOTP, RFC 6238) : Google Authenticator,
 * Microsoft Authenticator… génèrent un code à 6 chiffres toutes les 30 secondes.
 */
class DoubleAuthentificationService
{
    /** Nombre de codes de récupération fournis à l'utilisateur. */
    public const NOMBRE_CODES_RECUPERATION = 8;

    public function __construct(private Google2FA $google2fa) {}

    /**
     * Nouvelle clé secrète (base32), à enregistrer chiffrée sur l'utilisateur.
     */
    public function genererSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Clé secrète découpée par groupes de 4, pour la saisie manuelle dans l'application.
     */
    public function cleLisible(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    /**
     * QR code (SVG) de l'URL otpauth:// à scanner avec l'application d'authentification.
     */
    public function qrCodeSvg(User $user): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name', 'NutriTrace'), $user->email, $user->two_factor_secret);

        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($url);

        // Sans le prologue XML, le SVG s'insère directement dans la page
        return trim(preg_replace('/^<\?xml.*?\?>/s', '', $svg));
    }

    /**
     * Vérifie un code à 6 chiffres (tolérance d'une période de 30 s, pour un léger décalage d'horloge).
     * Un code déjà utilisé est refusé pendant sa durée de validité : il ne peut pas être rejoué.
     */
    public function verifierCode(User $user, string $code): bool
    {
        if (! $user->two_factor_secret || ! $this->google2fa->verifyKey($user->two_factor_secret, $code, 1)) {
            return false;
        }

        return Cache::add("double-auth:{$user->id}:{$code}", true, now()->addSeconds(90));
    }

    /**
     * Nouveaux codes de récupération, au format XXXXX-XXXXX.
     *
     * @return list<string>
     */
    public function genererCodesRecuperation(): array
    {
        return collect(range(1, self::NOMBRE_CODES_RECUPERATION))
            ->map(fn () => Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5)))
            ->all();
    }
}
