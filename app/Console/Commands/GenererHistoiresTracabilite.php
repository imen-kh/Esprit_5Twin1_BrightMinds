<?php

namespace App\Console\Commands;

use App\Models\Lot;
use App\Services\TracabiliteIAService;
use Illuminate\Console\Command;

/**
 * Pré-génère les histoires IA des lots (module Traçabilité) : la page publique les affiche
 * ensuite depuis la base, même sans connexion internet pendant la démonstration.
 */
class GenererHistoiresTracabilite extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tracabilite:generer-histoires
                            {--lot=* : Code(s) des lots à traiter (tous les lots ayant des étapes par défaut)}
                            {--force : Régénère même les histoires encore à jour}';

    /**
     * @var string
     */
    protected $description = 'Génère par IA (Gemini) l\'histoire des lots dont la chaîne a changé depuis la dernière génération';

    public function handle(TracabiliteIAService $ia): int
    {
        if (! $ia->estConfigure()) {
            $this->warn('GEMINI_API_KEY est vide dans .env : aucune histoire générée.');

            return self::SUCCESS;
        }

        $lots = Lot::query()
            ->has('etapes')
            ->when($this->option('lot'), fn ($query, array $codes) => $query->whereIn('code', array_map('strtoupper', $codes)))
            ->orderBy('code')
            ->get();

        if ($lots->isEmpty()) {
            $this->warn('Aucun lot à traiter.');

            return self::SUCCESS;
        }

        $generees = $aJour = $echecs = 0;

        foreach ($lots as $lot) {
            if (! $this->option('force') && $lot->histoireAJour()) {
                $aJour++;
                $this->line("  {$lot->code} : histoire déjà à jour");

                continue;
            }

            if ($ia->histoire($lot, forcer: true) !== null) {
                $generees++;
                $this->info("  {$lot->code} : histoire générée");
            } else {
                $echecs++;
                $this->error("  {$lot->code} : échec (voir storage/logs/laravel.log)");
            }
        }

        $this->newLine();
        $this->info("{$generees} générée(s), {$aJour} déjà à jour, {$echecs} échec(s).");

        return self::SUCCESS;
    }
}
