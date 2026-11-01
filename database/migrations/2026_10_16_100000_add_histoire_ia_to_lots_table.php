<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Histoire du produit rédigée par IA, mise en cache sur le lot avec sa date de génération.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->text('histoire_ia')->nullable()->after('notes');
            $table->timestamp('histoire_ia_generee_le')->nullable()->after('histoire_ia');
        });
    }

    /**
     * Retire les colonnes de l'histoire IA.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['histoire_ia', 'histoire_ia_generee_le']);
        });
    }
};
