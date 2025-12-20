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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone', 20); // E.164 format
            $table->text('message_content'); // Final message after merge tag replacement
            $table->string('sender_id', 11);
            $table->integer('parts_count')->default(1);
            $table->decimal('cost', 8, 2); // Cost in TZS
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'expired'])->default('queued');
            $table->string('beem_message_id')->nullable(); // Message ID from Beem
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('dlr_payload')->nullable(); // Raw DLR data from Beem
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['beem_message_id']);
            $table->index(['phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
