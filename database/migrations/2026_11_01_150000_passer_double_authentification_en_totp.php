<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double authentification par application (TOTP, Google Authenticator) au lieu du code par email :
     * clé secrète et codes de récupération chiffrés (casts « encrypted »), date de confirmation.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_code', 'two_factor_expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            // Renseignée quand l'utilisateur a validé un premier code : la 2FA n'est active qu'à partir de là
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    /**
     * Retour à la double authentification par code email.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_code')->nullable();
            $table->timestamp('two_factor_expires_at')->nullable();
        });
    }
};
