<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('api_keys', 'rate_limit_per_min')) {
                $table->integer('rate_limit_per_min')->nullable()->after('permissions');
            }
            if (!Schema::hasColumn('api_keys', 'ip_allowlist')) {
                $table->json('ip_allowlist')->nullable()->after('rate_limit_per_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('api_keys', 'ip_allowlist')) {
                $table->dropColumn('ip_allowlist');
            }
            if (Schema::hasColumn('api_keys', 'rate_limit_per_min')) {
                $table->dropColumn('rate_limit_per_min');
            }
        });
    }
};

