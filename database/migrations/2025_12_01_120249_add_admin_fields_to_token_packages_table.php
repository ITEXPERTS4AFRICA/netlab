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
        Schema::table('token_packages', function (Blueprint $table) {
            $table->text('icon_svg')->nullable()->after('description');
            $table->integer('display_order')->default(0)->after('icon_svg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_packages', function (Blueprint $table) {
            $table->dropColumn(['icon_svg', 'display_order']);
        });
    }
};
