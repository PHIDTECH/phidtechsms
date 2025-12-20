<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sender_ids', function (Blueprint $table) {
            if (!Schema::hasColumn('sender_ids', 'sender_name')) {
                $table->string('sender_name', 11)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('sender_ids', 'business_name')) {
                $table->string('business_name')->nullable()->after('sender_name');
            }
            if (!Schema::hasColumn('sender_ids', 'business_type')) {
                $table->string('business_type')->nullable()->after('business_name');
            }
            if (!Schema::hasColumn('sender_ids', 'business_registration')) {
                $table->string('business_registration')->nullable()->after('business_type');
            }
            if (!Schema::hasColumn('sender_ids', 'business_address')) {
                $table->string('business_address')->nullable()->after('business_registration');
            }
            if (!Schema::hasColumn('sender_ids', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('business_address');
            }
            if (!Schema::hasColumn('sender_ids', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('sender_ids', 'business_license_path')) {
                $table->string('business_license_path')->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('sender_ids', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('business_license_path');
            }
            if (!Schema::hasColumn('sender_ids', 'additional_documents_paths')) {
                $table->json('additional_documents_paths')->nullable()->after('id_document_path');
            }
            if (!Schema::hasColumn('sender_ids', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sender_ids', 'application_date')) {
                $table->timestamp('application_date')->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('sender_ids', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('application_date');
            }
            if (!Schema::hasColumn('sender_ids', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('sender_ids', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('rejection_reason');
            }
        });

        // Backfill sender_name from legacy sender_id if needed
        if (Schema::hasColumn('sender_ids', 'sender_id') && Schema::hasColumn('sender_ids', 'sender_name')) {
            DB::statement("UPDATE sender_ids SET sender_name = COALESCE(sender_name, sender_id)");
        }
    }

    public function down(): void
    {
        // Non-destructive: keep new columns on rollback to avoid data loss.
    }
};

