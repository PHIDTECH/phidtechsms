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
        Schema::table('contact_imports', function (Blueprint $table) {
            // Add original_filename if it doesn't exist
            if (!Schema::hasColumn('contact_imports', 'original_filename')) {
                $table->string('original_filename')->after('filename');
            }
            
            // Add file_path if it doesn't exist
            if (!Schema::hasColumn('contact_imports', 'file_path')) {
                $table->string('file_path')->after('original_filename');
            }
            
            // Add metadata if it doesn't exist
            if (!Schema::hasColumn('contact_imports', 'metadata')) {
                $table->json('metadata')->nullable()->after('import_options');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_imports', function (Blueprint $table) {
            if (Schema::hasColumn('contact_imports', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
            
            if (Schema::hasColumn('contact_imports', 'file_path')) {
                $table->dropColumn('file_path');
            }
            
            if (Schema::hasColumn('contact_imports', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};