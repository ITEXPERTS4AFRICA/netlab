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
        $driver = DB::getDriverName();
        
        // SQLite ne supporte pas ALTER COLUMN pour modifier le type
        // SQLite est flexible avec les types, donc on peut ignorer cette migration
        if ($driver === 'sqlite') {
            return;
        }
        
        // Pour PostgreSQL
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cache ALTER COLUMN key TYPE varchar(512)');
            DB::statement('ALTER TABLE cache_locks ALTER COLUMN key TYPE varchar(512)');
        } 
        // Pour MySQL
        elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE cache MODIFY COLUMN `key` VARCHAR(512)');
            DB::statement('ALTER TABLE cache_locks MODIFY COLUMN `key` VARCHAR(512)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        // SQLite ne supporte pas ALTER COLUMN
        if ($driver === 'sqlite') {
            return;
        }
        
        // Pour PostgreSQL
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cache ALTER COLUMN key TYPE varchar(255)');
            DB::statement('ALTER TABLE cache_locks ALTER COLUMN key TYPE varchar(255)');
        }
        // Pour MySQL
        elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE cache MODIFY COLUMN `key` VARCHAR(255)');
            DB::statement('ALTER TABLE cache_locks MODIFY COLUMN `key` VARCHAR(255)');
        }
    }
};
