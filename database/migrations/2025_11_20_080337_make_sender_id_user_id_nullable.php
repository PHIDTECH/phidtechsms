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
        Schema::table('sender_ids', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Drop the unique constraint
            $table->dropUnique(['user_id', 'sender_id']);
            
            // Make user_id nullable
            $table->foreignId('user_id')->nullable()->change();
            
            // Re-add the foreign key constraint (nullable)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Add a new unique constraint that allows null user_id
            // This ensures sender_id is unique per user, but allows multiple null user_ids
            $table->unique(['user_id', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sender_ids', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['user_id']);
            
            // Drop the unique constraint
            $table->dropUnique(['user_id', 'sender_id']);
            
            // Make user_id non-nullable again
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint (non-nullable)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Re-add the unique constraint
            $table->unique(['user_id', 'sender_id']);
        });
    }
};
