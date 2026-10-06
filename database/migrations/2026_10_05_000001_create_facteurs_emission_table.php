<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facteurs_emission', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->unique();
            $table->string('categorie'); // transport, production, emballage, energie, eau, autre
            $table->decimal('valeur', 10, 4); // ex: 0.1800 (kg CO2e par unité)
            $table->string('unite'); // kg CO2e / km, kg CO2e / kg, kg CO2e / kWh, etc.
            $table->string('source')->nullable(); // ADEME, ANME Tunisie, GIEC, Agribalyse
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facteurs_emission');
    }
};
