<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les champs du profil (tous facultatifs), selon le rôle de l'utilisateur.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Champs communs à tous les rôles
            $table->string('telephone', 20)->nullable();
            $table->string('avatar')->nullable(); // chemin du fichier sur le disque « public »

            // Champs de l'acteur de la chaîne (ville sert aussi au consommateur)
            $table->string('nom_entreprise')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('matricule_fiscal')->nullable();
            $table->text('description')->nullable();
            $table->string('site_web')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Champs du consommateur
            $table->json('preferences')->nullable(); // bio, local, equitable
            $table->string('allergies')->nullable();
        });
    }

    /**
     * Supprime les champs du profil.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telephone',
                'avatar',
                'nom_entreprise',
                'adresse',
                'ville',
                'matricule_fiscal',
                'description',
                'site_web',
                'latitude',
                'longitude',
                'preferences',
                'allergies',
            ]);
        });
    }
};
