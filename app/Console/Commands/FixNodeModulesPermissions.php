<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixNodeModulesPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'npm:fix-permissions 
                            {--user= : Nom d\'utilisateur pour les permissions (défaut: utilisateur actuel)}
                            {--group= : Nom du groupe pour les permissions (défaut: groupe de l\'utilisateur)}
                            {--remove : Supprimer node_modules avant de corriger}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les permissions de node_modules (sans droits shell)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Correction des permissions node_modules');
        $this->newLine();

        $nodeModulesPath = base_path('node_modules');
        $packageLockPath = base_path('package-lock.json');

        // Vérifier si node_modules existe
        if (!File::exists($nodeModulesPath)) {
            $this->warn('⚠️  Le dossier node_modules n\'existe pas');
            $this->info('💡 Exécutez: npm install (après avoir corrigé les permissions)');
            return 0;
        }

        // Option: Supprimer node_modules
        if ($this->option('remove')) {
            $this->info('1. Suppression de node_modules...');
            if ($this->confirm('⚠️  Voulez-vous vraiment supprimer node_modules ?', false)) {
                try {
                    File::deleteDirectory($nodeModulesPath);
                    $this->info('   ✅ node_modules supprimé');
                } catch (\Exception $e) {
                    $this->error('   ❌ Erreur lors de la suppression: ' . $e->getMessage());
                    $this->warn('   💡 Essayez de supprimer manuellement: rm -rf node_modules');
                    return 1;
                }
            } else {
                $this->info('   ℹ️  Suppression annulée');
            }
            $this->newLine();
        }

        // Obtenir l'utilisateur et le groupe
        $user = $this->option('user') ?: $this->getCurrentUser();
        $group = $this->option('group') ?: $this->getCurrentGroup();

        $this->info('2. Correction des permissions...');
        $this->info("   Utilisateur: {$user}");
        $this->info("   Groupe: {$group}");
        $this->newLine();

        // Corriger les permissions avec chmod et chown via exec
        $commands = [
            // Corriger les permissions du dossier node_modules
            "chmod -R u+rwX,go+rX,go-w {$nodeModulesPath}",
            
            // Corriger le propriétaire (si on a les droits)
            "chown -R {$user}:{$group} {$nodeModulesPath}",
            
            // Corriger package-lock.json si il existe
            "chmod 644 {$packageLockPath}",
            "chown {$user}:{$group} {$packageLockPath}",
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($commands as $command) {
            $this->line("   Exécution: {$command}");
            
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode === 0) {
                $successCount++;
                $this->info("   ✅ Succès");
            } else {
                $errorCount++;
                $errorMsg = implode("\n", $output);
                
                // Ignorer les erreurs de chown si on n'a pas les droits (normal)
                if (str_contains($command, 'chown') && str_contains($errorMsg, 'Operation not permitted')) {
                    $this->warn("   ⚠️  Pas les droits pour chown (normal si vous n'êtes pas root)");
                } else {
                    $this->error("   ❌ Erreur: {$errorMsg}");
                }
            }
        }

        $this->newLine();
        
        if ($errorCount === 0 || ($errorCount > 0 && str_contains($errorMsg ?? '', 'Operation not permitted'))) {
            $this->info('✅ Permissions corrigées !');
            $this->newLine();
            $this->info('💡 Vous pouvez maintenant exécuter: npm install');
        } else {
            $this->warn('⚠️  Certaines opérations ont échoué');
            $this->newLine();
            $this->info('💡 Solutions alternatives:');
            $this->line('   1. Exécuter en tant que root: sudo php artisan npm:fix-permissions');
            $this->line('   2. Supprimer node_modules et réinstaller:');
            $this->line('      php artisan npm:fix-permissions --remove');
            $this->line('      npm install');
        }

        return 0;
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    private function getCurrentUser(): string
    {
        $user = get_current_user();
        if (empty($user)) {
            $user = exec('whoami') ?: 'www-data';
        }
        return $user;
    }

    /**
     * Obtenir le groupe de l'utilisateur actuel
     */
    private function getCurrentGroup(): string
    {
        $group = exec('id -gn') ?: 'www-data';
        return $group;
    }
}

