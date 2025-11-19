<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin par défaut si il n'existe pas
        $admin = User::firstOrCreate(
            ['email' => 'admin@netlab.local'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✅ Utilisateur administrateur créé : admin@netlab.local / password');
        } else {
            $this->command->info('ℹ️  Utilisateur administrateur existe déjà : admin@netlab.local');
        }

        // Créer un deuxième admin optionnel pour les tests
        $testAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Test',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($testAdmin->wasRecentlyCreated) {
            $this->command->info('✅ Utilisateur admin test créé : admin@example.com / admin123');
        }
    }
}
