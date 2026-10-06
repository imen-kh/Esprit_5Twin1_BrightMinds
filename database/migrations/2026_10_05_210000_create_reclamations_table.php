<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('products')->cascadeOnDelete();
            // Pas de table commandes dans le projet : numéro libre, sans clé étrangère.
            $table->unsignedBigInteger('commande_id')->nullable()->index();
            $table->string('sujet');
            $table->text('description');
            $table->string('statut')->default('en_attente');
            $table->string('priorite')->default('moyenne');
            $table->string('sentiment')->nullable();
            $table->string('priorite_suggeree')->nullable();
            $table->text('suggestion_reponse')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
