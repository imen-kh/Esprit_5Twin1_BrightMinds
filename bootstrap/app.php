<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias des middlewares de rôle : 'admin' (admin seul) et 'acteur' (acteur ou admin)
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'acteur' => \App\Http\Middleware\ActeurMiddleware::class,
            'compte' => \App\Http\Middleware\GuestToRegister::class,
            'distributeur' => \App\Http\Middleware\DistributeurMiddleware::class,
            // spatie/laravel-permission : rôles et permissions (RolePermissionSeeder)
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Déconnexion sans vérification du jeton CSRF : une page restée ouverte longtemps
        // (jeton expiré) déconnecte quand même au lieu d'afficher « 419 Page expirée »
        $middleware->validateCsrfTokens(except: ['logout']);

        // Invité sur une page protégée -> connexion ; utilisateur connecté sur une page « guest » -> accueil
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
