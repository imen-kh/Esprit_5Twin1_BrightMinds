<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProduitSimilariteService;
use Tests\TestCase;

class ProduitSimilariteTest extends TestCase
{
    public function test_un_nom_court_different_n_est_pas_un_doublon(): void
    {
        $service = new ProduitSimilariteService;
        $pomme = new Product(['name' => 'Pomme']);
        $pommeDeTerre = new Product(['name' => 'Pomme de terre']);
        $huile = new Product(['name' => 'Huile d’olive Chemlali']);
        $variante = new Product(['name' => 'Huile olive Chemlali']);

        $this->assertFalse($service->sontSimilaires($pomme, $pommeDeTerre));
        $this->assertTrue($service->sontSimilaires($huile, $variante));
    }
}
