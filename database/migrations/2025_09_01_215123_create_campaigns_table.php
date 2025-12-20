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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('mode', ['file', 'group', 'quick']); // File SMS, Group SMS, Quick SMS
            $table->foreignId('sender_id')->constrained('sender_ids');
            $table->text('message'); // Message content with merge tags
            $table->timestamp('schedule_at')->nullable(); // null = send now
            $table->json('audience_config'); // Contains list_ids, numbers, upload_id
            $table->enum('status', ['draft', 'queued', 'processing', 'completed', 'failed', 'cancelled'])->default('draft');
            $table->integer('estimated_recipients')->default(0);
            $table->integer('estimated_parts')->default(0);
            $table->integer('hold_parts')->default(0); // Parts reserved for this campaign
            $table->decimal('estimated_cost', 10, 2)->default(0); // In TZS
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['schedule_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
