<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la colonne 'role' si elle existe encore
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recréer la colonne 'role' avec une méthode compatible
            $table->enum('role', ['admin', 'instructor', 'student'])->default('student')->after('remember_token');
        });
    }
};
