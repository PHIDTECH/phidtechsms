<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Expand enums to be compatible with existing runtime values
        DB::statement("ALTER TABLE `campaigns` MODIFY `mode` ENUM('file','group','quick','standard') NOT NULL");
        DB::statement("ALTER TABLE `campaigns` MODIFY `status` ENUM('draft','queued','processing','completed','failed','cancelled','pending','scheduled','sending') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Revert to original enum definitions
        DB::statement("ALTER TABLE `campaigns` MODIFY `mode` ENUM('file','group','quick') NOT NULL");
        DB::statement("ALTER TABLE `campaigns` MODIFY `status` ENUM('draft','queued','processing','completed','failed','cancelled') NOT NULL DEFAULT 'draft'");
    }
};