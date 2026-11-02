<?php

namespace App\Scout;

use Illuminate\Support\Facades\Log;
use Laravel\Scout\Engines\MeilisearchEngine;
use Throwable;

/**
 * Meilisearch ne doit jamais bloquer l'enregistrement d'un produit si le serveur est arrêté.
 */
class MoteurMeilisearchFiable extends MeilisearchEngine
{
    public function update($models)
    {
        try {
            parent::update($models);
        } catch (Throwable $e) {
            Log::warning('Recherche : indexation Meilisearch ignorée.', ['erreur' => $e->getMessage()]);
        }
    }

    public function delete($models)
    {
        try {
            parent::delete($models);
        } catch (Throwable $e) {
            Log::warning('Recherche : suppression Meilisearch ignorée.', ['erreur' => $e->getMessage()]);
        }
    }

    public function flush($model)
    {
        try {
            parent::flush($model);
        } catch (Throwable $e) {
            Log::warning('Recherche : vidage Meilisearch ignoré.', ['erreur' => $e->getMessage()]);
        }
    }
}
