<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bilans_environnementaux', function (Blueprint $table) {
            $table->id();

            // Lien vers le module Produit (Catalogue)
            $table->unsignedBigInteger('produit_id')->nullable()->index();
            $table->string('nom_produit');

            // Références aux facteurs d'émission clés
            $table->foreignId('facteur_emission_id')->nullable()->constrained('facteurs_emission')->nullOnDelete();
            $table->foreignId('facteur_production_id')->nullable()->constrained('facteurs_emission')->nullOnDelete();
            $table->foreignId('facteur_transport_id')->nullable()->constrained('facteurs_emission')->nullOnDelete();
            $table->foreignId('facteur_emballage_id')->nullable()->constrained('facteurs_emission')->nullOnDelete();

            // Paramètres du produit
            $table->decimal('quantite_produit', 8, 2)->default(1.00);
            $table->string('unite_mesure')->default('kg');
            $table->string('origine_geographique')->nullable();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->string('mode_transport')->nullable(); // camion, train, bateau, avion
            $table->string('type_emballage')->nullable(); // vrac, carton, plastique, verre
            $table->decimal('poids_emballage_g', 8, 2)->default(0);
            $table->string('mode_culture')->nullable(); // bio, plein_champ, serre_chauffee, hydroponique
            $table->decimal('consommation_eau_l', 10, 2)->default(0);

            // Résultats du bilan carbone (kg CO2e)
            $table->decimal('co2_production', 10, 3)->default(0);
            $table->decimal('co2_transport', 10, 3)->default(0);
            $table->decimal('co2_emballage', 10, 3)->default(0);
            $table->decimal('co2_total', 10, 3)->default(0);

            // Éco-Score estimé (IA & Algorithme standardisé)
            $table->integer('score_numerique')->default(100); // 0 à 100
            $table->enum('eco_score', ['A', 'B', 'C', 'D', 'E'])->default('B');

            // Recommandations & Alternative locale (Fonctionnalité IA)
            $table->text('alternative_locale')->nullable();
            $table->text('recommandation_ia')->nullable();
            $table->decimal('reduction_co2_estimee_pourcent', 5, 2)->nullable(); // ex: -45.5%

            $table->enum('statut', ['valide', 'en_attente', 'archive'])->default('valide');
            $table->text('commentaires')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilans_environnementaux');
    }
};
