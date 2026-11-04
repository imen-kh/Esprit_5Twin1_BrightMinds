<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bilans_environnementaux', function (Blueprint $table) {
            $table->string('source_ia')->nullable()->after('reduction_co2_estimee_pourcent');
        });
    }

    public function down(): void
    {
        Schema::table('bilans_environnementaux', function (Blueprint $table) {
            $table->dropColumn('source_ia');
        });
    }
};
