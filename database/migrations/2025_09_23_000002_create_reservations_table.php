<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->foreignId('rate_id')->nullable()->constrained('rates')->nullOnDelete();
            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');
            $table->enum('status', ['pending','active','completed','cancelled'])->default('pending');
            $table->unsignedBigInteger('estimated_cents')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};


