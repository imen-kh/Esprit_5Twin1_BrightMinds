<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Photos des produits : elles sont hébergées sur Cloudinary pour être visibles par tous
 * les membres (le dossier storage/ n'est pas partagé par Git).
 */
class ProductImageService
{
    public const DOSSIER = 'nutritrace/products';

    /**
     * Envoie l'image sur Cloudinary et retourne son URL sécurisée (https).
     */
    public function envoyer(UploadedFile $fichier): string
    {
        try {
            $this->ensureConfigured();
            $resultat = $this->client()->uploadApi()->upload($fichier->getRealPath(), [
                'folder' => self::DOSSIER,
                'resource_type' => 'image',
            ]);

            return $resultat['secure_url'];
        } catch (Throwable $e) {
            if (config('services.cloudinary.fallback_local', true)) {
                $path = $fichier->store(self::DOSSIER, 'public');

                Log::warning('Cloudinary indisponible : image enregistrée localement.', [
                    'erreur' => $e->getMessage(),
                    'path' => $path,
                ]);

                return $path;
            }

            Log::error('Envoi Cloudinary impossible : '.$e->getMessage());

            throw ValidationException::withMessages([
                'image' => "L'image n'a pas pu être envoyée. Vérifiez la connexion et la configuration Cloudinary.",
            ]);
        }
    }

    /**
     * Supprime une image : sur Cloudinary si c'est une URL, sinon dans le stockage local
     * (anciennes photos enregistrées avant Cloudinary).
     */
    public function supprimer(?string $image): void
    {
        if (! $image) {
            return;
        }

        if (! self::estDistante($image)) {
            Storage::disk('public')->delete($image);

            return;
        }

        $publicId = self::publicId($image);
        if (! $publicId) {
            return;
        }

        try {
            $this->client()->uploadApi()->destroy($publicId, ['resource_type' => 'image']);
        } catch (Throwable $e) {
            // La suppression du produit ne doit pas échouer parce que Cloudinary est injoignable
            Log::warning('Suppression Cloudinary impossible : '.$e->getMessage());
        }
    }

    public static function estDistante(string $image): bool
    {
        return str_starts_with($image, 'https://') || str_starts_with($image, 'http://');
    }

    /**
     * Retrouve le public_id à partir de l'URL :
     * https://res.cloudinary.com/<cloud>/image/upload/v123/nutritrace/products/abc.jpg → nutritrace/products/abc
     */
    public static function publicId(string $url): ?string
    {
        $chemin = parse_url($url, PHP_URL_PATH);
        if (! $chemin || ! str_contains($chemin, '/upload/')) {
            return null;
        }

        $apresUpload = substr($chemin, strpos($chemin, '/upload/') + strlen('/upload/'));
        $apresUpload = preg_replace('#^v\d+/#', '', $apresUpload);
        $publicId = preg_replace('/\.[A-Za-z0-9]+$/', '', $apresUpload);

        return $publicId !== '' ? rawurldecode($publicId) : null;
    }

    private function client(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    private function ensureConfigured(): void
    {
        foreach (['cloud_name', 'api_key', 'api_secret'] as $setting) {
            if (! filled(config('services.cloudinary.'.$setting))) {
                throw new \RuntimeException('Configuration Cloudinary incomplète : '.$setting);
            }
        }
    }
}
