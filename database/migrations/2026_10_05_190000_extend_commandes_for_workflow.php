<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->decimal('total', 10, 3)->change();
            $table->foreignId('distributeur_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('code_postal', 20)->nullable()->after('ville');
            $table->text('commentaire')->nullable()->after('code_postal');
            $table->timestamp('date_commande')->nullable()->after('statut')->index();
        });

        Schema::table('commande_produit', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 10, 3)->change();
            $table->decimal('sous_total', 10, 3)->default(0)->after('prix_unitaire');
            $table->unique(['commande_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('commande_produit', function (Blueprint $table) {
            $table->dropUnique(['commande_id', 'product_id']);
            $table->dropColumn('sous_total');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['distributeur_id']);
            $table->dropColumn(['distributeur_id', 'code_postal', 'commentaire', 'date_commande']);
        });
    }
};
