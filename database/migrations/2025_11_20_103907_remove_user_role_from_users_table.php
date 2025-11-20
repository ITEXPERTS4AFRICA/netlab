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
        // Convertir tous les utilisateurs avec le rôle 'user' en 'student'
        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'student']);

        // Pour PostgreSQL, on doit modifier le type ENUM différemment
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL : Supprimer la valeur par défaut temporairement
            DB::statement("ALTER TABLE users ALTER COLUMN role DROP DEFAULT");
            
            // Créer un nouveau type ENUM sans 'user'
            DB::statement("CREATE TYPE user_role_new AS ENUM ('admin', 'instructor', 'student')");
            
            // Convertir la colonne pour utiliser le nouveau type
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE user_role_new USING role::text::user_role_new");
            
            // Supprimer l'ancien type et renommer le nouveau
            DB::statement("DROP TYPE IF EXISTS user_role");
            DB::statement("ALTER TYPE user_role_new RENAME TO user_role");
            
            // Remettre la valeur par défaut
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'student'::user_role");
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL : Supprimer la valeur par défaut temporairement
            DB::statement("ALTER TABLE users ALTER COLUMN role DROP DEFAULT");
            
            // Créer un nouveau type ENUM avec 'user'
            DB::statement("CREATE TYPE user_role_old AS ENUM ('admin', 'instructor', 'student', 'user')");
            
            // Convertir la colonne
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE user_role_old USING role::text::user_role_old");
            
            // Supprimer l'ancien type et renommer
            DB::statement("DROP TYPE IF EXISTS user_role");
            DB::statement("ALTER TYPE user_role_old RENAME TO user_role");
            
            // Remettre la valeur par défaut
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'::user_role");
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'instructor', 'student', 'user') NOT NULL DEFAULT 'user'");
        }
    }
};
