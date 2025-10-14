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
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable()->after('email_verified_at');
            $table->boolean('notification_enabled')->default(true)->after('preferences');
            $table->string('notification_type')->default('both')->after('notification_enabled'); // email, browser, both
            $table->string('timezone')->default('UTC')->after('notification_type');
            $table->string('language')->default('en')->after('timezone');
            $table->boolean('auto_start_labs')->default(false)->after('language');
            $table->integer('notification_advance_minutes')->default(15)->after('auto_start_labs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'preferences',
                'notification_enabled',
                'notification_type',
                'timezone',
                'language',
                'auto_start_labs',
                'notification_advance_minutes'
            ]);
        });
    }
};
