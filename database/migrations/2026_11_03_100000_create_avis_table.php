<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedTinyInteger('note');
            $table->string('titre')->nullable();
            $table->text('commentaire');
            // publie, en_moderation (retenu par l'IA ou signalé), masque (décision de l'administrateur)
            $table->string('statut')->default('publie')->index();
            // Le client a reçu ce produit dans une commande livrée
            $table->boolean('achat_verifie')->default(false);
            // Analyse IA : sentiment, notes par aspect (goût, fraîcheur…), raison d'une modération
            $table->string('sentiment')->nullable();
            $table->json('aspects')->nullable();
            $table->string('moderation_raison')->nullable();
            $table->boolean('probleme_detecte')->default(false);
            // Droit de réponse du producteur
            $table->text('reponse_producteur')->nullable();
            $table->timestamp('reponse_producteur_at')->nullable();
            $table->unsignedInteger('utile_count')->default(0);
            $table->unsignedInteger('signalements_count')->default(0);
            $table->timestamps();

            // Un seul avis par client et par produit
            $table->unique(['user_id', 'produit_id']);
        });

        Schema::create('avis_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // utile, signalement
            $table->string('motif')->nullable();
            $table->timestamps();

            $table->unique(['avis_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_reactions');
        Schema::dropIfExists('avis');
    }
};
