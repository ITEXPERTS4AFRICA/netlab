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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // reservation_reminder, lab_available, reservation_confirmed, system_alert
            $table->string('title');
            $table->text('message');
            $table->string('priority', 20)->default('medium'); // low, medium, high
            $table->string('category', 50)->nullable(); // lab, system, reservation
            $table->json('data')->nullable(); // Additional data (reservation_id, lab_id, etc.)
            $table->string('action_url')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
