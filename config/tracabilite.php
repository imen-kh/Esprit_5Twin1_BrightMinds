<?php

/**
 * Seuils des contrôles automatiques du module Traçabilité (Lot::anomalies()).
 */
return [

    // Température maximale (°C) tolérée pendant un transport ou un stockage : au-delà, la chaîne du froid est rompue
    'temperature_max' => (float) env('TRACABILITE_TEMPERATURE_MAX', 8),

    // Vitesse moyenne maximale crédible (km/h) entre deux étapes successives
    'vitesse_max_kmh' => 120,

    // Distance totale maximale (km) pour un produit annoncé « local »
    'distance_locale_max_km' => 300,

    // Nombre de jours avant la péremption à partir duquel l'alerte « Bientôt périmé » s'affiche
    'peremption_alerte_jours' => 3,

];
