<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("ALTER TABLE `categories` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'validee'");
            DB::statement("ALTER TABLE `categories` ADD CONSTRAINT `categories_status_check` CHECK (`status` IN ('proposee', 'validee'))");
        } else {
            DB::statement("ALTER TABLE `categories` ADD `status` VARCHAR(20) NOT NULL DEFAULT 'validee' AFTER `description`");
            DB::statement("ALTER TABLE `categories` ADD CONSTRAINT `categories_status_check` CHECK (`status` IN ('proposee', 'validee'))");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `categories` DROP CHECK `categories_status_check`');
        DB::statement('ALTER TABLE `categories` DROP COLUMN `status`');
    }
};
