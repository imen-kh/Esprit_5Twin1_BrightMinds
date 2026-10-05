<?php

namespace App\Services;

use App\Models\Lot;

/**
 * Fonctions d'intelligence artificielle du module Traçabilité.
 *
 * Classe préparée pour une prochaine étape : les méthodes ne sont PAS encore implémentées
 * et ne font aucun appel externe. Elles fixent seulement le contrat attendu par les vues.
 */
class TracabiliteIAService
{
    /**
     * Rédigera l'histoire du produit à partir de sa chaîne complète, pour la page publique.
     *
     * Prévu : envoyer à un modèle de langage le produit, le lot et les étapes de
     * $lot->chaineComplete() (type, lieu, date, acteur, température), et demander un récit
     * court et factuel, sans rien inventer au-delà des étapes enregistrées.
     *
     * @return string texte de l'histoire, ou chaîne vide tant que la fonction n'est pas implémentée
     */
    public function genererHistoire(Lot $lot): string
    {
        // À venir : appel au modèle de langage.
        return '';
    }

    /**
     * Signalera les incohérences d'une chaîne de traçabilité (lutte contre le greenwashing).
     *
     * Prévu : repérer par exemple une rupture de la chaîne du froid (température hors plage
     * pendant un transport ou un stockage), un trajet impossible entre deux étapes (distance
     * et délai incompatibles), une étape manquante, ou une date de péremption dépassée.
     *
     * @return array<int, array{type: string, message: string, etape_id: int|null}> liste des anomalies,
     *                                                                             vide tant que la fonction n'est pas implémentée
     */
    public function detecterIncoherences(Lot $lot): array
    {
        // À venir : règles métier et/ou appel au modèle de langage.
        return [];
    }
}
