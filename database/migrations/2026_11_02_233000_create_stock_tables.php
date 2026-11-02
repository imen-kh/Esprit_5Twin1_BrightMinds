<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('culture_name', 100);
            $table->decimal('seuil_kg', 12, 1)->default(20);
            $table->decimal('kg_par_unite', 8, 3)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'culture_name']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('quantite_kg', 12, 1);
            $table->string('source', 20)->default('manuel');
            $table->string('motif')->nullable();
            $table->foreignId('harvest_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('commande_produit_id')->nullable()->unique()->constrained('commande_produit')->cascadeOnDelete();
            $table->date('moved_at');
            $table->timestamps();

            $table->index(['stock_item_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
    }
};
