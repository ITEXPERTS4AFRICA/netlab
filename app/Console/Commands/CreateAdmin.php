<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name= : Nom de l\'administrateur}
                            {--email= : Email de l\'administrateur}
                            {--password= : Mot de passe de l\'administrateur}
                            {--force : Forcer la création même si l\'utilisateur existe déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un utilisateur administrateur';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔐 Création d\'un utilisateur administrateur');
        $this->newLine();

        // Récupérer ou demander les informations
        $name = $this->option('name') ?: $this->ask('Nom de l\'administrateur', 'Administrateur');
        $email = $this->option('email') ?: $this->ask('Email de l\'administrateur');
        $password = $this->option('password') ?: $this->secret('Mot de passe (minimum 8 caractères)');
        $force = $this->option('force');

        // Valider les données
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('❌ Erreurs de validation :');
            foreach ($validator->errors()->all() as $error) {
                $this->error("   - {$error}");
            }
            return 1;
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser && !$force) {
            if (!$this->confirm("L'utilisateur avec l'email {$email} existe déjà. Voulez-vous le mettre à jour ?", false)) {
                $this->info('❌ Opération annulée.');
                return 0;
            }
        }

        // Créer ou mettre à jour l'utilisateur
        try {
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            if ($existingUser && $force) {
                $existingUser->update($userData);
                $user = $existingUser;
                $this->info("✅ Utilisateur administrateur mis à jour avec succès !");
            } else {
                $user = User::create($userData);
                $this->info("✅ Utilisateur administrateur créé avec succès !");
            }

            $this->newLine();
            $this->table(
                ['Champ', 'Valeur'],
                [
                    ['ID', $user->id],
                    ['Nom', $user->name],
                    ['Email', $user->email],
                    ['Rôle', $user->role],
                    ['Actif', $user->is_active ? 'Oui' : 'Non'],
                    ['Email vérifié', $user->email_verified_at ? 'Oui' : 'Non'],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la création : {$e->getMessage()}");
            return 1;
        }
    }
}

