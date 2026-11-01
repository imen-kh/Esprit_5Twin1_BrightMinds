<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : avant RefreshDatabase (qui vide la base), on vérifie que les tests tournent
     * sur SQLite en mémoire (phpunit.xml) et jamais sur la base MySQL de développement,
     * par exemple si la configuration a été mise en cache avec « php artisan config:cache ».
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connexion = config('database.default');
        $base = config("database.connections.{$connexion}.database");

        if ($connexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "Tests interrompus : la base utilisée est « {$connexion} / {$base} » au lieu de SQLite :memory:. "
                .'Lancez « php artisan config:clear » et vérifiez phpunit.xml.'
            );
        }

        // Aucun appel réseau réel (Gemini…) : toute requête HTTP doit être simulée avec Http::fake
        Http::preventStrayRequests();

        return parent::setUpTraits();
    }
}
