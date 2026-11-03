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

        // « UPDATE … JOIN » est propre à MySQL ; SQLite (tests et CI) passe par une sous-requête équivalente
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE products p INNER JOIN users u ON u.id = p.user_id SET p.type_produit = 'transforme' WHERE u.type_acteur = 'transformateur'");
        } else {
            DB::table('products')
                ->whereIn('user_id', DB::table('users')->where('type_acteur', 'transformateur')->select('id'))
                ->update(['type_produit' => 'transforme']);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type_produit');
        });
    }
};
