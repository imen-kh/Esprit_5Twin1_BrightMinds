<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le rôle de l'utilisateur : admin, acteur ou consommateur.
     * type_acteur n'est renseigné que si role = acteur : producteur, transformateur ou distributeur.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('consommateur')->after('email');
            $table->string('type_acteur')->nullable()->after('role');
        });
    }

    /**
     * Supprime les colonnes role et type_acteur.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'type_acteur']);
        });
    }
};
