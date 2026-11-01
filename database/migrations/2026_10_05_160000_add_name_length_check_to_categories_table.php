<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Syntaxe propre à MySQL : ignorée sous SQLite (base en mémoire des tests et de la CI),
        // où la longueur minimale reste contrôlée par la validation du formulaire
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `categories` ADD CONSTRAINT `categories_name_min_length` CHECK (CHAR_LENGTH(TRIM(`name`)) >= 3)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `categories` DROP CHECK `categories_name_min_length`');
    }
};
