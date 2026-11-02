<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->date('planted_at');
            $table->decimal('area_hectares', 8, 2);
            $table->decimal('estimated_kg', 12, 1);
            $table->decimal('harvested_kg', 12, 1)->nullable();
            $table->date('planned_harvest_at');
            $table->string('status', 20)->default('planifiee');
            $table->timestamps();

            $table->index(['user_id', 'planned_harvest_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
