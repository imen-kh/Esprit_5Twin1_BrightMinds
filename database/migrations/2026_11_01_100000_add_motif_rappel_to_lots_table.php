<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Motif du rappel d'un lot, affiché dans la bannière de la page publique.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->text('motif_rappel')->nullable()->after('statut');
        });
    }

    /**
     * Retire le motif de rappel.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('motif_rappel');
        });
    }
};
