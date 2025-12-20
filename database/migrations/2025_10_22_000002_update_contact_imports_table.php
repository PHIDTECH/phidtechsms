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
            if (!Schema::hasColumn('contact_imports', 'file_path')) {
                $table->string('file_path')->after('original_filename')->nullable();
            }

            if (!Schema::hasColumn('contact_imports', 'metadata')) {
                $table->json('metadata')->nullable()->after('validation_errors');
            }

            if (Schema::hasColumn('contact_imports', 'import_options')) {
                $table->renameColumn('import_options', 'legacy_import_options');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_imports', function (Blueprint $table) {
            if (Schema::hasColumn('contact_imports', 'file_path')) {
                $table->dropColumn('file_path');
            }

            if (Schema::hasColumn('contact_imports', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('contact_imports', 'legacy_import_options')) {
                $table->renameColumn('legacy_import_options', 'import_options');
            }
        });
    }
};
