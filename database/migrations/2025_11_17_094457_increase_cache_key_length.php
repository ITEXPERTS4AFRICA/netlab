<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Augmenter la taille de la colonne key dans la table cache
        DB::statement('ALTER TABLE cache ALTER COLUMN key TYPE varchar(512)');

        // Augmenter aussi pour cache_locks si nécessaire
        DB::statement('ALTER TABLE cache_locks ALTER COLUMN key TYPE varchar(512)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à 255 caractères (peut échouer si des clés plus longues existent)
        DB::statement('ALTER TABLE cache ALTER COLUMN key TYPE varchar(255)');
        DB::statement('ALTER TABLE cache_locks ALTER COLUMN key TYPE varchar(255)');
    }
};
