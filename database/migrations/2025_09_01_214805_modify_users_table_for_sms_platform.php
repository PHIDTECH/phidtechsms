<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->after('email');
            $table->enum('role', ['owner', 'admin', 'client_admin', 'operator'])->default('client_admin')->after('phone');
            $table->integer('wallet_balance')->default(0)->after('role'); // SMS parts balance
            $table->boolean('phone_verified')->default(false)->after('wallet_balance');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_verified');
            $table->string('otp_code', 6)->nullable()->after('phone_verified_at');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->boolean('two_factor_enabled')->default(false)->after('otp_expires_at');
            $table->string('two_factor_method')->nullable()->after('two_factor_enabled'); // sms, email
            $table->boolean('is_active')->default(true)->after('two_factor_method');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'role', 'wallet_balance', 'phone_verified', 'phone_verified_at',
                'otp_code', 'otp_expires_at', 'two_factor_enabled', 'two_factor_method',
                'is_active', 'last_login_at'
            ]);
        });
    }
};
