<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Problème récurrent détecté sur un produit (réclamations + avis négatifs rapprochés)
        Schema::create('alertes_qualite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->unsignedInteger('nb_signaux')->default(0);
            // Identifiants des signaux à l'origine de l'alerte : l'analyse IA est refaite s'ils changent
            $table->string('signature');
            $table->json('signaux')->nullable();
            $table->string('titre');
            $table->text('resume')->nullable();
            $table->string('gravite')->default('moyenne'); // faible, moyenne, elevee
            $table->text('recommandation')->nullable();
            $table->boolean('rappel_conseille')->default(false);
            $table->string('motif_rappel', 500)->nullable();
            $table->boolean('analyse_ia')->default(false);
            // ouverte, rappel_declenche, classee
            $table->string('statut')->default('ouverte')->index();
            $table->foreignId('traitee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traitee_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes_qualite');
    }
};
