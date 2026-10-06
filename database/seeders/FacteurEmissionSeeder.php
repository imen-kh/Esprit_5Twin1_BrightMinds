<?php

namespace Database\Seeders;

use App\Models\FacteurEmission;
use Illuminate\Database\Seeder;

class FacteurEmissionSeeder extends Seeder
{
    public function run(): void
    {
        $facteurs = [
            ['nom' => 'Transport routier — Camion diesel 16-32t', 'code' => 'TRSP_CAMION_DIESEL', 'categorie' => 'transport', 'valeur' => 0.1600, 'unite' => 'kg CO2e / t.km', 'source' => 'Base Empreinte ADEME & ANME', 'description' => 'Moyenne transport routier de marchandises en Tunisie et Méditerranée.'],
            ['nom' => 'Transport ferroviaire — Fret', 'code' => 'TRSP_FERROVIAIRE', 'categorie' => 'transport', 'valeur' => 0.0450, 'unite' => 'kg CO2e / t.km', 'source' => 'ADEME', 'description' => 'Transport de marchandises par train.'],
            ['nom' => 'Transport maritime — Cargo porte-conteneurs', 'code' => 'TRSP_MARITIME_CARGO', 'categorie' => 'transport', 'valeur' => 0.0350, 'unite' => 'kg CO2e / t.km', 'source' => 'OMI / IMO & Base Carbone', 'description' => 'Fret maritime standard en Méditerranée.'],
            ['nom' => 'Transport aérien — Fret court/moyen-courrier', 'code' => 'TRSP_AVION_FRET', 'categorie' => 'transport', 'valeur' => 1.8500, 'unite' => 'kg CO2e / t.km', 'source' => 'GIEC / OACI', 'description' => 'Fret avion pour denrées hautement périssables hors saison.'],
            ['nom' => 'Agriculture biologique certifiée — Plein champ', 'code' => 'PROD_AGRI_BIO', 'categorie' => 'production', 'valeur' => 0.4200, 'unite' => 'kg CO2e / kg produit', 'source' => 'Agribalyse & FAO', 'description' => 'Sans engrais de synthèse ni pesticides chimiques.'],
            ['nom' => 'Culture plein champ conventionnelle raisonnée', 'code' => 'PROD_PLEIN_CHAMP', 'categorie' => 'production', 'valeur' => 0.8500, 'unite' => 'kg CO2e / kg produit', 'source' => 'INRAT Tunisie & Agribalyse', 'description' => 'Pratiques agricoles tunisiennes standards de saison.'],
            ['nom' => 'Culture sous serre chauffée au gaz/fioul', 'code' => 'PROD_SERRE_GAZ', 'categorie' => 'production', 'valeur' => 2.4500, 'unite' => 'kg CO2e / kg produit', 'source' => 'Base Carbone ADEME', 'description' => 'Serres chauffées artificiellement hors saison.'],
            ['nom' => 'Culture hydroponique sous abri non chauffé', 'code' => 'PROD_HYDROPONIQUE', 'categorie' => 'production', 'valeur' => 1.3500, 'unite' => 'kg CO2e / kg produit', 'source' => 'INRAT & CIRAD', 'description' => 'Cultures hors-sol économes en eau.'],
            ['nom' => 'Plastique PET / Polyéthylène à usage unique', 'code' => 'EMB_PLASTIQUE_PET', 'categorie' => 'emballage', 'valeur' => 2.8000, 'unite' => 'kg CO2e / kg emballage', 'source' => 'PlasticsEurope & ADEME', 'description' => 'Barquettes, films et sachets plastiques non recyclés.'],
            ['nom' => 'Carton ondulé recyclé & papier kraft', 'code' => 'EMB_CARTON_RECYCLE', 'categorie' => 'emballage', 'valeur' => 0.7200, 'unite' => 'kg CO2e / kg emballage', 'source' => 'FEFCO', 'description' => 'Emballages en papier ou carton recyclé.'],
            ['nom' => 'Bocal / Bouteille en verre standard', 'code' => 'EMB_VERRE', 'categorie' => 'emballage', 'valeur' => 1.1500, 'unite' => 'kg CO2e / kg emballage', 'source' => 'Glass for Europe', 'description' => 'Contenant en verre réutilisable ou recyclable.'],
            ['nom' => 'Vrac / Cagette réutilisable consignée', 'code' => 'EMB_VRAC', 'categorie' => 'emballage', 'valeur' => 0.0500, 'unite' => 'kg CO2e / kg emballage', 'source' => 'Réseau Vrac & ADEME', 'description' => 'Rotation de contenants durables, sans emballage jetable.'],
            ['nom' => 'Électricité réseau STEG moyenne tension (Tunisie)', 'code' => 'ENERG_STEG_TN', 'categorie' => 'energie', 'valeur' => 0.4650, 'unite' => 'kg CO2e / kWh', 'source' => 'STEG / ANME Tunisie', 'description' => 'Facteur moyen du mix électrique national tunisien.'],
            ['nom' => 'Eau d\'irrigation agricole — Pompage solaire / gravitaire', 'code' => 'EAU_IRRIG_SOLAIRE', 'categorie' => 'eau', 'valeur' => 0.0018, 'unite' => 'kg CO2e / m3', 'source' => 'CRDA Tunisie & FAO', 'description' => 'Eau d\'irrigation pompée à l\'énergie photovoltaïque.'],
        ];

        foreach ($facteurs as $facteur) {
            FacteurEmission::query()->updateOrCreate(
                ['code' => $facteur['code']],
                FacteurEmission::factory()->raw([
                    ...$facteur,
                    'actif' => true,
                ])
            );
        }
    }
}
