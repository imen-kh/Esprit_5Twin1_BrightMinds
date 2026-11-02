<?php

namespace Database\Seeders;

use App\Models\BilanEnvironnemental;
use App\Models\FacteurEmission;
use App\Services\EcoScoreIAService;
use Illuminate\Database\Seeder;

class EnvironnementSeeder extends Seeder
{
    public function run(): void
    {
        $facteurs = [
            // Transport
            [
                'nom' => 'Transport routier — Camion diesel 16-32t',
                'code' => 'TRSP_CAMION_DIESEL',
                'categorie' => 'transport',
                'valeur' => 0.1600,
                'unite' => 'kg CO2e / t.km',
                'source' => 'Base Empreinte ADEME & ANME',
                'description' => 'Moyenne transport routier de marchandises en Tunisie et Méditerranée.',
            ],
            [
                'nom' => 'Transport maritime — Cargo porte-conteneurs',
                'code' => 'TRSP_MARITIME_CARGO',
                'categorie' => 'transport',
                'valeur' => 0.0350,
                'unite' => 'kg CO2e / t.km',
                'source' => 'OMI / IMO & Base Carbone',
                'description' => 'Fret maritime standard en Méditerranée.',
            ],
            [
                'nom' => 'Transport aérien — Fret court/moyen-courrier',
                'code' => 'TRSP_AVION_FRET',
                'categorie' => 'transport',
                'valeur' => 1.8500,
                'unite' => 'kg CO2e / t.km',
                'source' => 'GIEC / OACI',
                'description' => 'Fret avion pour denrées hautement périssables hors saison.',
            ],
            [
                'nom' => 'Véhicule utilitaire léger électrique — Circuit court',
                'code' => 'TRSP_VUL_ELEC',
                'categorie' => 'transport',
                'valeur' => 0.0450,
                'unite' => 'kg CO2e / t.km',
                'source' => 'ANME Tunisie',
                'description' => 'Livraison urbaine et périurbaine en mobilité décarbonée.',
            ],

            // Production agricole
            [
                'nom' => 'Agriculture biologique certifiée — Plein champ',
                'code' => 'PROD_AGRI_BIO',
                'categorie' => 'production',
                'valeur' => 0.4200,
                'unite' => 'kg CO2e / kg produit',
                'source' => 'Agribalyse & FAO',
                'description' => 'Sans engrais de synthèse ni pesticides chimiques, stockage carbone sol.',
            ],
            [
                'nom' => 'Culture plein champ conventionnelle raisonnée',
                'code' => 'PROD_PLEIN_CHAMP',
                'categorie' => 'production',
                'valeur' => 0.8500,
                'unite' => 'kg CO2e / kg produit',
                'source' => 'INRAT Tunisie & Agribalyse',
                'description' => 'Pratiques agricoles tunisiennes standards de saison.',
            ],
            [
                'nom' => 'Culture sous serre chauffée au gaz/fioul',
                'code' => 'PROD_SERRE_GAZ',
                'categorie' => 'production',
                'valeur' => 2.4500,
                'unite' => 'kg CO2e / kg produit',
                'source' => 'Base Carbone ADEME',
                'description' => 'Serres climatisées ou chauffées artificiellement hors saison.',
            ],
            [
                'nom' => 'Culture hydroponique sous abri non chauffé',
                'code' => 'PROD_HYDROPONIQUE',
                'categorie' => 'production',
                'valeur' => 1.3500,
                'unite' => 'kg CO2e / kg produit',
                'source' => 'INRAT & CIRAD',
                'description' => 'Cultures hors-sol économes en eau mais intensives en nutriments minéraux.',
            ],

            // Emballage
            [
                'nom' => 'Plastique PET / Polyéthylène à usage unique',
                'code' => 'EMB_PLASTIQUE_PET',
                'categorie' => 'emballage',
                'valeur' => 2.8000,
                'unite' => 'kg CO2e / kg emballage',
                'source' => 'PlasticsEurope & ADEME',
                'description' => 'Barquettes, films et sachets plastiques non recyclés.',
            ],
            [
                'nom' => 'Carton ondulé recyclé & papier kraft',
                'code' => 'EMB_CARTON_RECYCLE',
                'categorie' => 'emballage',
                'valeur' => 0.7200,
                'unite' => 'kg CO2e / kg emballage',
                'source' => 'FEFCO',
                'description' => 'Emballages compostables ou issus de papier/carton recyclé.',
            ],
            [
                'nom' => 'Bocal / Bouteille en verre standard',
                'code' => 'EMB_VERRE',
                'categorie' => 'emballage',
                'valeur' => 1.1500,
                'unite' => 'kg CO2e / kg emballage',
                'source' => 'Glass for Europe',
                'description' => 'Contenant en verre inerte réutilisable ou recyclable.',
            ],
            [
                'nom' => 'Vrac / Cagette réutilisable consignée',
                'code' => 'EMB_VRAC',
                'categorie' => 'emballage',
                'valeur' => 0.0500,
                'unite' => 'kg CO2e / kg emballage',
                'source' => 'Réseau Vrac & ADEME',
                'description' => 'Zéro emballage jetable, rotation de contenants durables.',
            ],

            // Énergie & Eau
            [
                'nom' => 'Électricité réseau STEG moyenne tension (Tunisie)',
                'code' => 'ENERG_STEG_TN',
                'categorie' => 'energie',
                'valeur' => 0.4650,
                'unite' => 'kg CO2e / kWh',
                'source' => 'STEG / ANME Tunisie',
                'description' => 'Facteur moyen du mix électrique national tunisien (gaz naturel dominant).',
            ],
            [
                'nom' => 'Eau d\'irrigation agricole — Pompage solaire / gravitaire',
                'code' => 'EAU_IRRIG_SOLAIRE',
                'categorie' => 'eau',
                'valeur' => 0.0018,
                'unite' => 'kg CO2e / m3',
                'source' => 'CRDA Tunisie & FAO',
                'description' => 'Eau d\'irrigation agricole pompée à l\'énergie photovoltaïque.',
            ],
        ];

        foreach ($facteurs as $data) {
            FacteurEmission::query()->updateOrCreate(['code' => $data['code']], $data);
        }

        // Exemples de Bilans Environnementaux calculés via le service IA
        $serviceIA = new EcoScoreIAService();

        $exemplesBilans = [
            [
                'nom_produit' => 'Oranges Maltaises bio du Cap Bon',
                'quantite_produit' => 1.0,
                'unite_mesure' => 'kg',
                'origine_geographique' => 'Nabeul, Cap Bon, Tunisie',
                'distance_km' => 45,
                'mode_transport' => 'camion',
                'mode_culture' => 'bio',
                'type_emballage' => 'vrac',
                'poids_emballage_g' => 0,
                'consommation_eau_l' => 60,
                'statut' => 'valide',
                'commentaires' => 'Production agrumicole biologique en circuit ultra-court vers Tunis.',
            ],
            [
                'nom_produit' => 'Harissa artisanale du Cap Bon en pot verre',
                'quantite_produit' => 0.2, // pot 200g
                'unite_mesure' => 'pot 200g',
                'origine_geographique' => 'Menzel Bouzelfa, Tunisie',
                'distance_km' => 52,
                'mode_transport' => 'camion',
                'mode_culture' => 'bio',
                'type_emballage' => 'verre',
                'poids_emballage_g' => 120,
                'consommation_eau_l' => 25,
                'statut' => 'valide',
                'commentaires' => 'Piments locaux du terroir transformés sur place avec huile d\'olive locale.',
            ],
            [
                'nom_produit' => 'Tomates fraîches de saison (Sahel)',
                'quantite_produit' => 1.0,
                'unite_mesure' => 'kg',
                'origine_geographique' => 'Sousse / Chott Meriem, Tunisie',
                'distance_km' => 135,
                'mode_transport' => 'camion',
                'mode_culture' => 'plein_champ',
                'type_emballage' => 'carton',
                'poids_emballage_g' => 20,
                'consommation_eau_l' => 140,
                'statut' => 'valide',
                'commentaires' => 'Tomates de plein champ récoltées à maturité, transport régional.',
            ],
            [
                'nom_produit' => 'Tomates cerises sous serre importées',
                'quantite_produit' => 1.0,
                'unite_mesure' => 'kg',
                'origine_geographique' => 'Importé Espagne (Almería)',
                'distance_km' => 1450,
                'mode_transport' => 'camion',
                'mode_culture' => 'serre_chauffee',
                'type_emballage' => 'plastique',
                'poids_emballage_g' => 45,
                'consommation_eau_l' => 380,
                'statut' => 'valide',
                'commentaires' => 'Importation lointaine avec barquette plastique jetable et culture sous serre chauffée.',
            ],
            [
                'nom_produit' => 'Huile d\'olive extra-vierge Chemlali',
                'quantite_produit' => 1.0, // 1 litre
                'unite_mesure' => 'L',
                'origine_geographique' => 'Sfax / Agareb, Tunisie',
                'distance_km' => 260,
                'mode_transport' => 'camion',
                'mode_culture' => 'bio',
                'type_emballage' => 'verre',
                'poids_emballage_g' => 400,
                'consommation_eau_l' => 30,
                'statut' => 'valide',
                'commentaires' => 'Oliveraies pluviales traditionnelles de Sfax sans irrigation chimique.',
            ],
        ];

        foreach ($exemplesBilans as $exemple) {
            $calculs = $serviceIA->analyser($exemple, false);
            BilanEnvironnemental::query()->updateOrCreate(
                ['nom_produit' => $exemple['nom_produit']],
                array_merge($exemple, $calculs)
            );
        }
    }
}
