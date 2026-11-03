<?php

namespace App\Console\Commands;

use App\Services\AlerteQualiteService;
use Illuminate\Console\Command;

/**
 * Détection planifiée des alertes qualité (module Réclamations & Avis).
 * php artisan reclamations:alertes --ia   → détecte puis fait analyser les nouvelles alertes par DeepSeek
 */
class DetecterAlertesQualite extends Command
{
    protected $signature = 'reclamations:alertes {--ia : Analyse les alertes nouvelles ou modifiées avec DeepSeek}';

    protected $description = 'Détecte les produits visés par plusieurs réclamations ou avis négatifs récents';

    public function handle(AlerteQualiteService $service): int
    {
        $alertes = $service->detecter();

        foreach ($alertes as $alerte) {
            if ($this->option('ia') && ! $alerte->analyse_ia) {
                $service->analyser($alerte);
            }

            $this->line(sprintf('• %s — %d signaux — gravité %s%s', $alerte->produit?->name, $alerte->nb_signaux, $alerte->gravite, $alerte->analyse_ia ? ' (IA)' : ''));
        }

        $this->info($alertes->count().' alerte(s) ouverte(s).');

        return self::SUCCESS;
    }
}
