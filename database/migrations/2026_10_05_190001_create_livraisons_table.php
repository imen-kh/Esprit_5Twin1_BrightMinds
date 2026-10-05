<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->unique()->constrained('commandes')->cascadeOnDelete();
            $table->foreignId('distributeur_id')->constrained('users')->restrictOnDelete();
            $table->string('adresse_livraison');
            $table->string('ville', 100);
            $table->string('code_postal', 20);
            $table->string('telephone', 20);
            $table->string('statut')->default('en_preparation')->index();
            $table->string('numero_suivi', 100)->nullable()->unique();
            $table->timestamp('date_expedition')->nullable();
            $table->timestamp('date_livraison_prevue')->nullable();
            $table->timestamp('date_livraison')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
