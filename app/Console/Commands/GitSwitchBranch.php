<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GitSwitchBranch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:switch 
                            {branch=master : Nom de la branche à utiliser}
                            {--pull : Faire un pull après le switch}
                            {--force : Forcer le switch même avec des modifications locales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Changer de branche Git (sans droits shell)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $branch = $this->argument('branch');
        $pull = $this->option('pull');
        $force = $this->option('force');

        $this->info("🔄 Changement vers la branche '{$branch}'");
        $this->newLine();

        // Vérifier que nous sommes dans un dépôt Git
        if (!File::exists(base_path('.git'))) {
            $this->error('❌ Ce n\'est pas un dépôt Git');
            return 1;
        }

        // Vérifier l'état actuel
        $this->info('1. Vérification de l\'état Git...');
        $currentBranch = $this->execGit('rev-parse --abbrev-ref HEAD');
        
        if ($currentBranch === $branch) {
            $this->info("   ℹ️  Vous êtes déjà sur la branche '{$branch}'");
            
            if ($pull) {
                $this->newLine();
                $this->info('2. Pull depuis origin...');
                $this->execGit('pull origin ' . $branch);
            }
            
            return 0;
        }

        $this->info("   Branche actuelle: {$currentBranch}");

        // Vérifier les modifications locales
        $this->newLine();
        $this->info('2. Vérification des modifications locales...');
        $status = $this->execGit('status --porcelain');
        
        if (!empty($status) && !$force) {
            $this->warn('   ⚠️  Des modifications locales ont été détectées:');
            $this->line($status);
            $this->newLine();
            
            if (!$this->confirm('Voulez-vous continuer ? (les modifications seront conservées)', false)) {
                $this->info('❌ Opération annulée');
                return 0;
            }
        } elseif (!empty($status)) {
            $this->warn('   ⚠️  Modifications locales détectées (--force activé)');
        } else {
            $this->info('   ✅ Aucune modification locale');
        }

        // Changer de branche
        $this->newLine();
        $this->info("3. Changement vers la branche '{$branch}'...");
        
        $result = $this->execGit('checkout ' . $branch);
        
        if ($result === false) {
            // Si le checkout échoue, essayer de créer la branche depuis origin
            $this->warn('   ⚠️  La branche locale n\'existe pas, tentative de création depuis origin...');
            $this->execGit('fetch origin');
            $result = $this->execGit('checkout -b ' . $branch . ' origin/' . $branch);
        }

        if ($result === false) {
            $this->error('❌ Impossible de changer de branche');
            $this->error('   Vérifiez que la branche existe: git branch -a');
            return 1;
        }

        $this->info("   ✅ Changement vers '{$branch}' réussi");

        // Pull si demandé
        if ($pull) {
            $this->newLine();
            $this->info('4. Pull depuis origin...');
            $this->execGit('pull origin ' . $branch);
        }

        $this->newLine();
        $this->info('✅ Opération terminée !');

        return 0;
    }

    /**
     * Exécuter une commande Git
     */
    private function execGit(string $command): string|false
    {
        $fullCommand = 'git ' . $command . ' 2>&1';
        $output = [];
        $returnCode = 0;
        
        exec($fullCommand, $output, $returnCode);
        
        $result = implode("\n", $output);
        
        if ($returnCode !== 0) {
            $this->warn("   ⚠️  Git: {$result}");
            return false;
        }
        
        return trim($result);
    }
}

