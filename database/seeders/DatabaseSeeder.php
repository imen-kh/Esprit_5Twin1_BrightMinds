<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Comptes de démo (mot de passe : "password") : un admin, un acteur par type, un consommateur.
        // updateOrCreate : le seeder peut être relancé sans créer de doublons.
        $comptes = [
            [
                'name' => 'Admin NutriTrace',
                'email' => 'admin@nutritrace.tn',
                'role' => 'admin',
                'type_acteur' => null,
            ],
            [
                'name' => 'Ferme Ben Salah',
                'email' => 'producteur@nutritrace.tn',
                'role' => 'acteur',
                'type_acteur' => 'producteur',
                'telephone' => '22451780',
                'nom_entreprise' => 'Ferme Ben Salah',
                'adresse' => 'Route de Korba, km 4',
                'ville' => 'Nabeul',
                'matricule_fiscal' => '1284567/B/A/000',
                'description' => "Exploitation familiale d'agrumes et de légumes de saison, cultivés sans pesticides au Cap Bon.",
                'site_web' => 'https://ferme-bensalah.tn',
                'latitude' => 36.45,
                'longitude' => 10.73,
            ],
            [
                'name' => 'Conserverie du Cap Bon',
                'email' => 'transformateur@nutritrace.tn',
                'role' => 'acteur',
                'type_acteur' => 'transformateur',
                'telephone' => '72289340',
                'nom_entreprise' => 'Conserverie du Cap Bon',
                'adresse' => 'Zone industrielle, rue des Orangers',
                'ville' => 'Menzel Bouzelfa',
                'matricule_fiscal' => '0957314/K/P/000',
                'description' => "Transformation d'agrumes, de tomates et de harissa à partir de récoltes locales.",
                'site_web' => 'https://conserverie-capbon.tn',
                'latitude' => 36.68,
                'longitude' => 10.58,
            ],
            [
                'name' => 'Épicerie Bio Sousse',
                'email' => 'distributeur@nutritrace.tn',
                'role' => 'acteur',
                'type_acteur' => 'distributeur',
                'telephone' => '73224615',
                'nom_entreprise' => 'Épicerie Bio Sousse',
                'adresse' => '18 avenue Habib Bourguiba',
                'ville' => 'Sousse',
                'matricule_fiscal' => '1532896/M/C/000',
                'description' => 'Épicerie de produits bio et locaux, en circuit court avec les producteurs du Sahel.',
                'site_web' => 'https://epicerie-bio-sousse.tn',
                'latitude' => 35.8256,
                'longitude' => 10.6369,
            ],
            [
                'name' => 'Client NutriTrace',
                'email' => 'client@nutritrace.tn',
                'role' => 'consommateur',
                'type_acteur' => null,
                'telephone' => '98765432',
                'ville' => 'Tunis',
                'preferences' => ['bio', 'local'],
                'allergies' => 'Arachides',
            ],
        ];

        foreach ($comptes as $compte) {
            // L'email identifie le compte ; le reste (rôle, profil, mot de passe) est mis à jour
            User::updateOrCreate(
                ['email' => $compte['email']],
                [...$compte, 'password' => 'password'],
            );
        }

        // Chaque membre ajoute ici les seeders de son module, par exemple :
        $this->call([
            EnvironnementSeeder::class,
        ]);
    }
}
