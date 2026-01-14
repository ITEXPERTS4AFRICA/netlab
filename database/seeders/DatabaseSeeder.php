<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // On appelle ici le UserSeeder que nous avons créé avant
        $this->call([
            \Database\Seeders\UserSeeder::class,
        ]);
    }
}
