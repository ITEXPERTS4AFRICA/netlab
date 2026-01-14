<?php

namespace Database\Seeders;

// use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer un utilisateur de test optionnel
        // User::firstOrCreate(
        //     ['email' => 'test@example.com'],
        //     [
        //         'name' => 'Test User',
        //         'password' => Hash::make('password'),
        //         'role' => 'student',
        //         'is_active' => true,
        //         'email_verified_at' => now(),
        //     ]
        // );

        // Créer un instructeur de test
        // User::firstOrCreate(
        //     ['email' => 'instructor@example.com'],
        //     [
        //         'name' => 'Instructeur Test',
        //         'password' => Hash::make('password'),
        //         'role' => 'instructor',
        //         'is_active' => true,
        //         'email_verified_at' => now(),
        //     ]
        // );

         $this->call(UserSeeder::class);
    }
}
