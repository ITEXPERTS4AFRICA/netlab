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
        // 1. Create Token Packages table
        Schema::create('token_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('tokens');
            $table->integer('price_cents');
            $table->string('currency')->default('XOF');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create Token Transactions table
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // Positive for credit, negative for debit
            $table->string('type'); // purchase, reservation, refund, bonus, adjustment
            $table->string('description')->nullable();
            $table->string('reference_id')->nullable(); // ID of reservation or payment
            $table->timestamps();
        });

        // 3. Update Users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('tokens_balance')->default(500); // 500 free tokens for everyone
        });

        // 4. Update Labs table
        Schema::table('labs', function (Blueprint $table) {
            $table->integer('token_cost_per_hour')->default(10);
        });

        // 5. Update Reservations table
        Schema::table('reservations', function (Blueprint $table) {
            $table->integer('tokens_cost')->nullable();
            // Make estimated_cents nullable if it's not already, or we just use it for legacy/hybrid
            $table->integer('estimated_cents')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('tokens_cost');
            // We can't easily revert nullable change without raw SQL or knowing previous state perfectly, 
            // but usually it's fine to leave it nullable.
        });

        Schema::table('labs', function (Blueprint $table) {
            $table->dropColumn('token_cost_per_hour');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tokens_balance');
        });

        Schema::dropIfExists('token_transactions');
        Schema::dropIfExists('token_packages');
    }
};
