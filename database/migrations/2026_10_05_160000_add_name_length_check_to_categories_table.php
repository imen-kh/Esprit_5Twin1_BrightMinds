<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE `categories` ADD CONSTRAINT `categories_name_min_length` CHECK (CHAR_LENGTH(TRIM(`name`)) >= 3)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `categories` DROP CHECK `categories_name_min_length`');
    }
};
