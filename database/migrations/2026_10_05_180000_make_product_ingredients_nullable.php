<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `products` MODIFY `ingredients` TEXT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE `products` SET `ingredients` = '' WHERE `ingredients` IS NULL");
        DB::statement('ALTER TABLE `products` MODIFY `ingredients` TEXT NOT NULL');
    }
};
