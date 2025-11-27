<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanAndRestart extends Command
{
    protected $signature = 'app:clean-restart';
    protected $description = 'Nettoyer tous les caches et redémarrer les services';

    public function handle()
    {
        $this->info('🧹 Nettoyage des caches...');
        
        // Nettoyer tous les caches
        $this->call('optimize:clear');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        
        // Recréer les caches optimisés
        $this->info('⚡ Optimisation...');
        $this->call('config:cache');
        $this->call('route:cache');
        
        // Note: Pour régénérer l'autoload, exécutez: composer dump-autoload
        
        $this->info('✅ Cache nettoyé et services optimisés!');
        $this->info('💡 Pour redémarrer le serveur: php artisan serve');
        $this->info('💡 Pour redémarrer Vite: npm run dev');
        
        return 0;
    }
}

