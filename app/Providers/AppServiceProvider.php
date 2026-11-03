<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limiteurs nommés de la gestion des utilisateurs : chacun a son propre compteur
        // (un « throttle:6,1 » anonyme partage le même compteur entre toutes les routes d'une IP)

        // Lien « mot de passe oublié » et nouveau mot de passe : 6 essais par minute et par IP
        RateLimiter::for('mot-de-passe', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        // Vérification de l'email : 6 clics ou renvois par minute et par compte
        RateLimiter::for('verification-email', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?: $request->ip()));

        // Codes de double authentification : 5 essais par minute pour le compte en cours de connexion
        RateLimiter::for('deux-facteurs', fn (Request $request) => Limit::perMinute(5)
            ->by($request->session()->get('two_factor.user_id') ?: $request->ip()));

        // Premier code saisi dans le profil pour activer la double authentification
        RateLimiter::for('activation-deux-facteurs', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?: $request->ip()));
    }
}
