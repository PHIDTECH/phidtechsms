<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if both columns exist (partial migration scenario)
        if (Schema::hasColumn('users', 'wallet_balance') && Schema::hasColumn('users', 'sms_credits')) {
            // Copy wallet_balance data to sms_credits (convert to SMS credits)
            DB::statement('UPDATE users SET sms_credits = CAST(wallet_balance / 30 AS INTEGER) WHERE wallet_balance > 0');
            
            // Drop the old wallet_balance column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wallet_balance');
            });
        }
        // If only wallet_balance exists, rename it
        elseif (Schema::hasColumn('users', 'wallet_balance') && !Schema::hasColumn('users', 'sms_credits')) {
            // Convert existing wallet_balance values to SMS credits
            DB::statement('UPDATE users SET wallet_balance = CAST(wallet_balance / 30 AS INTEGER) WHERE wallet_balance > 0');
            
            // Rename wallet_balance column to sms_credits
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('wallet_balance', 'sms_credits');
            });
        }
        // If only sms_credits exists, migration already completed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'sms_credits')) {
            // Add wallet_balance column back
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('wallet_balance', 10, 2)->default(0)->after('role');
            });
            
            // Convert SMS credits back to wallet balance (multiply by 30)
            DB::statement('UPDATE users SET wallet_balance = sms_credits * 30 WHERE sms_credits > 0');
            
            // Drop sms_credits column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sms_credits');
            });
        }
    }
};
