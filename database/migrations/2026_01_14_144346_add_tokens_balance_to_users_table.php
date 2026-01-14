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
        // 1. On vérifie d'abord si la colonne existe déjà
        if (!Schema::hasColumn('users', 'tokens_balance')) {

            // 2. Si elle n'existe pas, on l'ajoute
            Schema::table('users', function (Blueprint $table) {
                $table->integer('tokens_balance')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tokens_balance');
        });
    }
};
