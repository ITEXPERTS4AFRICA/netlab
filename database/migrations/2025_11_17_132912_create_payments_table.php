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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('transaction_id')->unique();
            $table->string('cinetpay_transaction_id')->nullable();
            $table->unsignedBigInteger('amount'); // Montant en centimes
            $table->string('currency', 3)->default('XOF');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
            $table->string('payment_method')->nullable();
            $table->string('customer_name');
            $table->string('customer_surname')->nullable();
            $table->string('customer_email');
            $table->string('customer_phone_number');
            $table->text('description')->nullable();
            $table->json('cinetpay_response')->nullable();
            $table->json('webhook_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('cinetpay_transaction_id');
            $table->index('status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
