<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certifications')) {
            Schema::table('certifications', function (Blueprint $table): void {
                if (Schema::hasColumn('certifications', 'produit_id') && ! Schema::hasColumn('certifications', 'product_id')) {
                    $table->renameColumn('produit_id', 'product_id');
                }
                if (Schema::hasColumn('certifications', 'producteur_id') && ! Schema::hasColumn('certifications', 'acteur_id')) {
                    $table->renameColumn('producteur_id', 'acteur_id');
                }
            });

            return;
        }

        Schema::create('certifications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('label_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('acteur_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero', 255);
            $table->date('date_obtention');
            $table->date('date_expiration');
            $table->string('fichier_pdf', 255);
            $table->enum('statut', ['en_attente', 'validee', 'refusee', 'expiree'])
                ->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->timestamps();

            $table->unique(['label_id', 'numero']);
            $table->index('statut');
            $table->index('date_expiration');
            $table->index('acteur_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        // Existing certification data must not be removed by this alignment migration.
    }
};
