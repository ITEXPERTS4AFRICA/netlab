<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CmlConfigSeeder extends Seeder
{
    /**
     * Migrer la configuration CML du .env vers la base de données
     */
    public function run(): void
    {
        // Migrer les valeurs du .env vers la base de données si elles n'existent pas déjà
        $baseUrl = env('CML_API_BASE_URL');
        $username = env('CML_USERNAME');
        $password = env('CML_PASSWORD');

        if ($baseUrl && !Setting::where('key', 'cml.base_url')->exists()) {
            Setting::set('cml.base_url', $baseUrl, 'string', 'URL de base de l\'API CML');
        }

        if ($username && !Setting::where('key', 'cml.username')->exists()) {
            Setting::set('cml.username', $username, 'string', 'Nom d\'utilisateur CML');
        }

        if ($password && !Setting::where('key', 'cml.password')->exists()) {
            Setting::set('cml.password', $password, 'string', 'Mot de passe CML', true); // Crypter le mot de passe
        }
    }
}
