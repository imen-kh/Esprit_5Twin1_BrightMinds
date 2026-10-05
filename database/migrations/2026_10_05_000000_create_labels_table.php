<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('nom', 255)->unique();
            $table->string('organisme', 255);
            $table->text('description');
            $table->string('logo', 255);
            $table->unsignedTinyInteger('duree_validite_mois')->default(12);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
