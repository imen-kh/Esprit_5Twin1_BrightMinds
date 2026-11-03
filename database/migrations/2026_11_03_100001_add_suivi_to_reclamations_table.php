<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            // Référence lisible donnée au client : REC-2026-0042
            $table->string('reference')->nullable()->unique()->after('id');
            $table->foreignId('lot_id')->nullable()->after('commande_id')->constrained('lots')->nullOnDelete();
            $table->foreignId('avis_id')->nullable()->after('lot_id')->constrained('avis')->nullOnDelete();
            // formulaire, assistant (agent Sahbi), avis (avis négatif converti)
            $table->string('source')->default('formulaire')->after('avis_id');

            // Analyse IA
            $table->string('categorie')->nullable()->after('priorite');
            $table->string('langue', 8)->nullable()->after('categorie');
            $table->text('resume_ia')->nullable()->after('suggestion_reponse');
            $table->string('responsable_suggere')->nullable()->after('resume_ia');
            $table->text('responsable_explication')->nullable()->after('responsable_suggere');

            // Suivi du délai de traitement et satisfaction du client
            $table->timestamp('echeance_at')->nullable()->after('responsable_explication');
            $table->timestamp('resolue_at')->nullable()->after('echeance_at');
            $table->unsignedTinyInteger('satisfaction_note')->nullable()->after('resolue_at');
            $table->text('satisfaction_commentaire')->nullable()->after('satisfaction_note');
        });

        Schema::create('historique_reclamations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')->constrained('reclamations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // creation, statut, reponse, ia, responsable, escalade, satisfaction, rappel
            $table->string('type');
            $table->string('libelle');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_reclamations');

        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('avis_id');
            $table->dropConstrainedForeignId('lot_id');
            $table->dropUnique(['reference']);
            $table->dropColumn([
                'reference', 'source', 'categorie', 'langue', 'resume_ia', 'responsable_suggere',
                'responsable_explication', 'echeance_at', 'resolue_at', 'satisfaction_note', 'satisfaction_commentaire',
            ]);
        });
    }
};
