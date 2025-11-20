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
        Schema::create('warnings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUlid('issued_by')->constrained('users')->onDelete('cascade')->comment('Admin ou Instructor qui a émis l\'avertissement');
            $table->string('reason', 500);
            $table->text('details')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['issued_by', 'created_at']);
        });

        // Table pour les banissements
        Schema::create('user_bans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUlid('banned_by')->constrained('users')->onDelete('cascade')->comment('Admin ou Instructor qui a banni');
            $table->string('reason', 500);
            $table->text('details')->nullable();
            $table->timestamp('banned_until')->nullable()->comment('Null = bannissement permanent');
            $table->boolean('is_permanent')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'banned_until']);
            $table->index(['banned_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bans');
        Schema::dropIfExists('warnings');
    }
};
