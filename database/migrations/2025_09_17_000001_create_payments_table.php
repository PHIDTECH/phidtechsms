<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->decimal('amount', 12, 2);
                $t->integer('credits');
                $t->string('currency', 10)->default('TZS');
                $t->string('payment_method', 50)->default('selcom');
                $t->string('status', 20)->default('pending')->index();
                $t->string('reference')->unique();
                $t->string('gateway_transaction_id')->nullable()->index();
                $t->json('gateway_response')->nullable();
                $t->json('webhook_data')->nullable();
                $t->json('metadata')->nullable();
                $t->timestamp('completed_at')->nullable()->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

