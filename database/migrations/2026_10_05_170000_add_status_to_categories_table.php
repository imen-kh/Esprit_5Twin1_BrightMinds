<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('categories', 'status')) {
            return;
        }

        // SQLite (tests et CI) : même colonne via le Schema Builder, sans la contrainte CHECK propre à MySQL
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('status', 20)->default('validee');
            });

            return;
        }

        DB::statement("ALTER TABLE `categories` ADD `status` VARCHAR(20) NOT NULL DEFAULT 'validee' AFTER `description`");
        DB::statement("ALTER TABLE `categories` ADD CONSTRAINT `categories_status_check` CHECK (`status` IN ('proposee', 'validee'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            return;
        }

        DB::statement('ALTER TABLE `categories` DROP CHECK `categories_status_check`');
        DB::statement('ALTER TABLE `categories` DROP COLUMN `status`');
    }
};
