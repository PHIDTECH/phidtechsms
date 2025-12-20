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
        Schema::table('contacts', function (Blueprint $table) {
            // Add missing columns that don't exist yet
            if (!Schema::hasColumn('contacts', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('contacts', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('email');
            }
            if (!Schema::hasColumn('contacts', 'contact_group_id')) {
                $table->foreignId('contact_group_id')->nullable()->constrained()->onDelete('set null')->after('user_id');
            }
            if (!Schema::hasColumn('contacts', 'beem_contact_id')) {
                $table->string('beem_contact_id')->nullable()->after('contact_group_id');
            }
            if (!Schema::hasColumn('contacts', 'metadata')) {
                $table->json('metadata')->nullable()->after('beem_contact_id');
            }
            if (!Schema::hasColumn('contacts', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('contacts', 'opt_out')) {
                $table->boolean('opt_out')->default(false)->after('custom_fields');
            }
            if (!Schema::hasColumn('contacts', 'last_contacted_at')) {
                $table->timestamp('last_contacted_at')->nullable()->after('opt_out');
            }
            
            $table->index(['user_id', 'opt_out']);
            $table->index(['beem_contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'opt_out']);
            $table->dropIndex(['beem_contact_id']);
            
            $columns_to_drop = [];
            if (Schema::hasColumn('contacts', 'email')) $columns_to_drop[] = 'email';
            if (Schema::hasColumn('contacts', 'date_of_birth')) $columns_to_drop[] = 'date_of_birth';
            if (Schema::hasColumn('contacts', 'contact_group_id')) $columns_to_drop[] = 'contact_group_id';
            if (Schema::hasColumn('contacts', 'beem_contact_id')) $columns_to_drop[] = 'beem_contact_id';
            if (Schema::hasColumn('contacts', 'metadata')) $columns_to_drop[] = 'metadata';
            if (Schema::hasColumn('contacts', 'custom_fields')) $columns_to_drop[] = 'custom_fields';
            if (Schema::hasColumn('contacts', 'opt_out')) $columns_to_drop[] = 'opt_out';
            if (Schema::hasColumn('contacts', 'last_contacted_at')) $columns_to_drop[] = 'last_contacted_at';
            
            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
