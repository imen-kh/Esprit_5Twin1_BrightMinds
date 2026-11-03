<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (tests et CI) : « MODIFY » n'existe pas, le Schema Builder reconstruit la colonne
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->text('ingredients')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE `products` MODIFY `ingredients` TEXT NULL');
    }

    public function down(): void
    {
        DB::table('products')->whereNull('ingredients')->update(['ingredients' => '']);

        if (DB::getDriverName() !== 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->text('ingredients')->nullable(false)->change();
            });

            return;
        }
        DB::statement('ALTER TABLE `products` MODIFY `ingredients` TEXT NOT NULL');
    }
};
