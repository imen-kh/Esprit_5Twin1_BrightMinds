<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Module Réclamations & Avis : alertes qualité analysées par DeepSeek toutes les heures
Schedule::command('reclamations:alertes --ia')->hourly()->withoutOverlapping();
