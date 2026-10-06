<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type_produit', 30)->default('matiere_premiere')->after('status')->index();
        });

        DB::statement("UPDATE products SET type_produit = 'transforme' WHERE user_id IN (SELECT id FROM users WHERE type_acteur = 'transformateur')");
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type_produit');
        });
    }
};
