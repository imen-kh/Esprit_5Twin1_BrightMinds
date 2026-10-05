<?php

namespace App\Services;

/**
 * Point d'entrée du calcul de score environnemental.
 * La formule existante reste dans EcoScoreIAService : ce service ne fait que la déléguer.
 */
class EnvironmentalScoreService
{
    public function __construct(private readonly EcoScoreIAService $ecoScoreIAService)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function calculer(array $data): array
    {
        return $this->ecoScoreIAService->analyser($data);
    }
}
