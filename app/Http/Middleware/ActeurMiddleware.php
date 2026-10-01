<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActeurMiddleware
{
    /**
     * Réserve l'accès aux acteurs de la chaîne et aux administrateurs (403 pour les autres rôles).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isActeur() && ! $user?->isAdmin()) {
            abort(403, 'Accès réservé aux acteurs de la chaîne.');
        }

        return $next($request);
    }
}
