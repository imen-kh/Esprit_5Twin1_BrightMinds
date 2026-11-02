<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->string('culture_name', 100)->nullable()->after('product_id');
        });

        foreach (DB::table('harvests')->whereNull('culture_name')->get() as $recolte) {
            $nom = DB::table('products')->where('id', $recolte->product_id)->value('name');

            if ($nom) {
                DB::table('harvests')->where('id', $recolte->id)->update(['culture_name' => $nom]);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `harvests` MODIFY `product_id` BIGINT UNSIGNED NULL');

            return;
        }

        Schema::table('harvests', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('harvests')->whereNull('product_id')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `harvests` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('harvests', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable(false)->change();
            });
        }

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn('culture_name');
        });
    }
};
