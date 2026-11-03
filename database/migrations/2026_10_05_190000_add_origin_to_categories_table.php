<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'origin')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('origin', 20)->default('admin')->after('status');
            });
        }

        if (! Schema::hasColumn('categories', 'user_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('origin')->constrained()->nullOnDelete();
            });
        }

        // Contrainte CHECK propre à MySQL : ignorée sous SQLite (tests et CI)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `categories` ADD CONSTRAINT `categories_origin_check` CHECK (`origin` IN ('admin', 'producteur'))");
        }

        DB::table('categories')->where('status', 'proposee')->update(['origin' => 'producteur']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `categories` DROP CHECK `categories_origin_check`');
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('origin');
        });
    }
};
