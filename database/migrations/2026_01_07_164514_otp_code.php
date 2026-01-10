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
        Schema::create('otp_codes', function (Blueprint $table){
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn('failed_attempts');
        });
    }
};
