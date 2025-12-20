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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['topup', 'sms_cost', 'refund', 'adjustment']); // Transaction types
            $table->decimal('amount', 10, 2); // Amount in TZS
            $table->decimal('balance_before', 10, 2); // Balance before transaction
            $table->decimal('balance_after', 10, 2); // Balance after transaction
            $table->string('description'); // Human readable description
            $table->string('reference')->nullable(); // Payment reference or campaign ID
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_method')->nullable(); // selcom, manual, etc.
            $table->string('payment_reference')->nullable(); // Selcom transaction ID
            $table->json('payment_metadata')->nullable(); // Raw payment gateway data
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['payment_reference']);
            $table->index(['reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
