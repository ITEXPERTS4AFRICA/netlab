<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // 1. Administrateur statique
        // IMPORTANT: 'role' => 'admin' (Doit exister dans votre ENUM SQL)
        User::create([
            'id' => Str::ulid(), // Génération explicite de l'ULID (optionnel mais propre)
            'name' => 'Super Admin',
            'email' => 'admin@netlab.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff',
            'bio' => 'Compte administrateur système.',
            'phone' => '+225 01 02 03 04',
            'organization' => 'NETLAB',
            'department' => 'DSI',
            'position' => 'Admin Système',
            'skills' => json_encode(['Cisco', 'Linux', 'Securité']),
            'certifications' => json_encode(['CCIE']),
            'education' => 'Ingénieur Réseau',
            'total_reservations' => 0,
            'total_labs_completed' => 0,
            'last_activity_at' => now(),
            'metadata' => json_encode(['is_root' => true]),
            'cml_username' => 'admin',
            'cml_user_id' => 0,
            'cml_admin' => true,
            'cml_groups' => json_encode(['admin']),
            'cml_resource_pool_id' => null,
            'cml_pubkey' => null,
            'cml_directory_dn' => null,
            'cml_opt_in' => false,
            'cml_tour_version' => 0,
            'cml_token' => null,
            'cml_token_expires_at' => null,
            'cml_owned_labs' => json_encode([]),
        ]);
        

        // 2. Utilisateurs Aléatoires
        // On génère 50 utilisateurs
        for ($i = 0; $i < 50; $i++) {
            // CORRECTION ICI : On utilise 'student', 'instructor' au lieu de 'user'
            $role = $faker->randomElement(['student', 'instructor']);

            User::create([
                // Pas besoin de préciser 'id' si c'est géré par le modèle ou la migration par défaut,
                // mais on peut laisser Laravel le générer.
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => $faker->boolean(90),
                'avatar' => $faker->randomElement([null, $faker->imageUrl(200, 200, 'people')]),
                'bio' => $faker->sentence(8),
                'phone' => $faker->phoneNumber,
                'organization' => $faker->company,
                'department' => $faker->randomElement(['Informatique', 'Réseaux', 'Gestion']),
                'position' => $faker->jobTitle,
                'skills' => json_encode($faker->randomElements(['Python', 'Bash', 'Ansible', 'Cisco'], 2)),
                'certifications' => json_encode($faker->randomElements(['CCNA', 'CCNP'], 1)),
                'education' => $faker->randomElement(['Licence', 'Master']),
                'total_reservations' => $faker->numberBetween(0, 10),
                'total_labs_completed' => $faker->numberBetween(0, 5),
                'last_activity_at' => $faker->dateTimeThisYear,
                'metadata' => json_encode(['source' => 'faker']),

                // Champs CML
                'cml_username' => $faker->userName,
                'cml_user_id' => $faker->numberBetween(1000, 9000),
                'cml_admin' => false,
                'cml_groups' => json_encode([$role === 'instructor' ? 'instructor' : 'student']),
                'cml_resource_pool_id' => $faker->uuid,
                'cml_pubkey' => $faker->sha256,
                'cml_directory_dn' => 'cn=' . $faker->userName . ',dc=netlab,dc=local',
                'cml_opt_in' => true,
                'cml_tour_version' => 1,
                'cml_token' => Hash::make(Str::random(10)),
                'cml_token_expires_at' => $faker->dateTimeBetween('+1 month', '+1 year'),
                'cml_owned_labs' => json_encode([]),
            ]);
        }
    }
}
