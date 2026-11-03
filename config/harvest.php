<?php

return [

    /*
    | Fenêtre indicative, en jours après la plantation.
    | Ce n'est pas une date de récolte garantie : le producteur saisit la sienne.
    */
    'cycles' => [
        'defaut' => ['min' => 60, 'max' => 120],
        'tomate' => ['min' => 70, 'max' => 100],
        'fraise' => ['min' => 60, 'max' => 90],
        'salade' => ['min' => 30, 'max' => 60],
        'piment' => ['min' => 80, 'max' => 120],
        'oignon' => ['min' => 90, 'max' => 150],
        'carotte' => ['min' => 70, 'max' => 110],
        'concombre' => ['min' => 50, 'max' => 70],
        'courgette' => ['min' => 45, 'max' => 70],
        'pomme de terre' => ['min' => 90, 'max' => 140],
        'orange' => ['min' => 180, 'max' => 270],
        'citron' => ['min' => 180, 'max' => 270],
        'datte' => ['min' => 150, 'max' => 220],
        'pomme' => ['min' => 120, 'max' => 180],
    ],

];
