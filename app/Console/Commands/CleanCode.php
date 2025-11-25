<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CleanCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:clean 
                            {--optimize : Optimiser l\'autoloader}
                            {--cache : Vider tous les caches}
                            {--all : Tout nettoyer (cache, config, routes, views)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer le code et les caches de l\'application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Nettoyage du code');
        $this->newLine();

        $optimize = $this->option('optimize');
        $cache = $this->option('cache');
        $all = $this->option('all');

        if ($all) {
            $optimize = true;
            $cache = true;
        }

        // Vider les caches
        if ($cache || $all) {
            $this->info('1. Vidage des caches...');
            
            $commands = [
                'config:clear' => 'Configuration',
                'route:clear' => 'Routes',
                'view:clear' => 'Vues',
                'cache:clear' => 'Cache applicatif',
                'event:clear' => 'Événements',
            ];

            foreach ($commands as $command => $label) {
                try {
                    Artisan::call($command);
                    $this->info("   ✅ Cache {$label} vidé");
                } catch (\Exception $e) {
                    $this->warn("   ⚠️  Erreur lors du vidage du cache {$label}: {$e->getMessage()}");
                }
            }
            
            $this->newLine();
        }

        // Optimiser l'autoloader
        if ($optimize || $all) {
            $this->info('2. Optimisation de l\'autoloader...');
            try {
                Artisan::call('optimize:clear');
                $this->info('   ✅ Autoloader optimisé');
            } catch (\Exception $e) {
                $this->warn("   ⚠️  Erreur lors de l'optimisation: {$e->getMessage()}");
            }
            $this->newLine();
        }

        // Nettoyer les fichiers temporaires
        $this->info('3. Nettoyage des fichiers temporaires...');
        $tempPaths = [
            storage_path('logs/*.log'),
            storage_path('framework/cache/data/*'),
            storage_path('framework/sessions/*'),
            storage_path('framework/views/*'),
        ];

        $cleaned = 0;
        foreach ($tempPaths as $pattern) {
            $files = glob($pattern);
            foreach ($files as $file) {
                if (is_file($file) && @unlink($file)) {
                    $cleaned++;
                }
            }
        }

        if ($cleaned > 0) {
            $this->info("   ✅ {$cleaned} fichier(s) temporaire(s) supprimé(s)");
        } else {
            $this->info('   ℹ️  Aucun fichier temporaire à supprimer');
        }

        $this->newLine();
        $this->info('✅ Nettoyage terminé !');

        return 0;
    }
}

