<?php

/**
 * Règles métier du module Réclamations & Avis.
 */
return [

    // Délai de traitement (SLA) en heures, selon la priorité : au-delà, la réclamation est « en retard »
    'sla_heures' => [
        'urgente' => 24,
        'haute' => 48,
        'moyenne' => 72,
        'faible' => 120,
    ],

    // Alerte qualité : nombre de signaux (réclamations + avis ≤ 2★) sur un même produit dans la fenêtre
    'alerte_seuil' => (int) env('RECLAMATIONS_ALERTE_SEUIL', 3),
    'alerte_fenetre_jours' => (int) env('RECLAMATIONS_ALERTE_FENETRE', 14),

    // Avis : un avis signalé par ce nombre de personnes repasse en modération
    'avis_seuil_signalements' => 3,

    // Agent Sahbi : nombre maximal d'appels d'outils par message, et de messages gardés en contexte
    'assistant_iterations_max' => 4,
    'assistant_historique' => 16,

];
