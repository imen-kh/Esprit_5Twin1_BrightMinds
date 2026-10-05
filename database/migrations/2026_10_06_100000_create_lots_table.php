<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lots de production : un lot appartient à un produit du Catalogue.
     */
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // format LOT-AAAA-NNNN
            // Produit du module Catalogue (table « products »)
            $table->foreignId('produit_id')->constrained('products')->cascadeOnDelete();
            // Lot de matière première dont est issu un lot transformé (fraises -> confiture)
            $table->foreignId('lot_origine_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->date('date_production');
            $table->date('date_peremption')->nullable();
            $table->unsignedInteger('quantite');
            $table->string('unite'); // kg, L, unités, caisses
            $table->string('statut')->default('en_production'); // en_production, en_transit, disponible, rappele
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table des lots.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
