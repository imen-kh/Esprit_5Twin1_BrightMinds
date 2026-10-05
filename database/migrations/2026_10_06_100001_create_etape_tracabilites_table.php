<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Étapes de traçabilité : chaque étape d'un lot est réalisée par un acteur.
     */
    public function up(): void
    {
        Schema::create('etape_tracabilites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            // Acteur qui réalise l'étape : on garde l'historique, un acteur ayant des étapes ne peut pas être supprimé
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type_etape'); // production, transformation, transport, stockage, distribution
            $table->string('titre');
            $table->string('lieu');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('date_etape');
            $table->decimal('temperature', 5, 2)->nullable(); // en °C
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table des étapes.
     */
    public function down(): void
    {
        Schema::dropIfExists('etape_tracabilites');
    }
};
