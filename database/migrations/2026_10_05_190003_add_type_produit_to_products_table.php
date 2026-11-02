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

        if (DB::getDriverName() === 'sqlite') {
            $transformateurIds = DB::table('users')
                ->where('type_acteur', 'transformateur')
                ->pluck('id');

            DB::table('products')
                ->whereIn('user_id', $transformateurIds)
                ->update(['type_produit' => 'transforme']);
        } else {
            DB::statement("UPDATE products p INNER JOIN users u ON u.id = p.user_id SET p.type_produit = 'transforme' WHERE u.type_acteur = 'transformateur'");
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type_produit');
        });
    }
};
