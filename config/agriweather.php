<?php

return [

    /*
    | Seuils d'alerte. Les cultures connues remplacent le jeu « defaut »
    | dès que leur nom apparaît dans un produit du producteur.
    | Unités : °C, mm de pluie sur la journée, km/h.
    */
    'seuils' => [
        'defaut' => [
            'pluie_mm' => 20,
            'chaleur_c' => 35,
            'gel_c' => 2,
            'vent_kmh' => 50,
        ],
        'tomate' => [
            'chaleur_c' => 32,
            'gel_c' => 5,
        ],
        'fraise' => [
            'chaleur_c' => 30,
            'gel_c' => 3,
            'pluie_mm' => 15,
        ],
        'pomme' => [
            'gel_c' => 0,
        ],
        'salade' => [
            'chaleur_c' => 30,
            'gel_c' => 2,
        ],
        'piment' => [
            'gel_c' => 6,
            'chaleur_c' => 36,
        ],
    ],

];
