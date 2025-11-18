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
        
        // Pour SQLite, on vérifie différemment
        if ($driver === 'sqlite') {
            // SQLite n'a pas besoin de cette migration car il utilise des types dynamiques
            // On vérifie juste si la colonne existe
            $tableInfo = DB::select("PRAGMA table_info(sessions)");
            $columnExists = collect($tableInfo)->contains(function ($column) {
                return $column->name === 'user_id';
            });
            
            if (!$columnExists) {
                // Si la colonne n'existe pas, la créer
                Schema::table('sessions', function (Blueprint $table) {
                    $table->foreignUlid('user_id')->nullable()->index();
                });
            }
            return;
        }
        
        // Pour PostgreSQL et MySQL
        if ($driver === 'pgsql') {
            // Vérifier si la colonne existe et son type actuel (PostgreSQL)
            $columnExists = DB::selectOne("
                SELECT column_name, data_type 
                FROM information_schema.columns 
                WHERE table_name = 'sessions' 
                AND column_name = 'user_id'
                AND table_schema = current_schema()
            ");
        } else {
            // MySQL
        $columnExists = DB::selectOne("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_name = 'sessions' 
            AND column_name = 'user_id'
                AND table_schema = DATABASE()
        ");
        }

        if ($columnExists) {
            // Si la colonne est de type bigint, on la modifie (PostgreSQL uniquement)
            if ($driver === 'pgsql' && $columnExists->data_type === 'bigint') {
                // Supprimer la contrainte de clé étrangère si elle existe
                DB::statement('ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_user_id_foreign');
                
                // Modifier le type de la colonne
                DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar(26) USING user_id::text');
                
                // Recréer la contrainte de clé étrangère
                DB::statement('
                    ALTER TABLE sessions 
                    ADD CONSTRAINT sessions_user_id_foreign 
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ');
            }
        } else {
            // Si la colonne n'existe pas, la créer
            Schema::table('sessions', function (Blueprint $table) {
                $table->foreignUlid('user_id')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir au type bigint (mais cela ne fonctionnera pas si des ULID sont présents)
        // On ne fait rien dans le down pour éviter de casser les données
    }
};
