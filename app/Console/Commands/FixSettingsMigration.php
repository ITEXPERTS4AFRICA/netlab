<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixSettingsMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fix-settings 
                            {--force : Forcer l\'exécution même si la table existe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marquer la migration settings comme exécutée si la table existe déjà';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Correction de la migration settings');
        $this->newLine();

        $migrationName = '2025_11_17_114322_create_settings_table';

        // Vérifier si la table settings existe
        $this->info('1. Vérification de la table settings...');
        if (Schema::hasTable('settings')) {
            $this->info('   ✅ La table settings existe déjà');
            $this->newLine();

            // Vérifier si la migration est enregistrée
            $this->info('2. Vérification de l\'enregistrement de la migration...');
            $migrationExists = DB::table('migrations')
                ->where('migration', $migrationName)
                ->exists();

            if ($migrationExists) {
                $this->info('   ✅ La migration est déjà enregistrée');
                $this->newLine();
                
                $this->info('3. Exécution des migrations restantes...');
                $this->call('migrate', ['--force' => true]);
            } else {
                $this->warn('   ⚠️  La migration n\'est pas enregistrée');
                $this->newLine();

                $this->info('3. Marquage de la migration comme exécutée...');
                
                // Obtenir le batch maximum
                $maxBatch = DB::table('migrations')->max('batch') ?? 0;
                $newBatch = $maxBatch + 1;

                // Insérer l'enregistrement de migration
                DB::table('migrations')->insert([
                    'migration' => $migrationName,
                    'batch' => $newBatch,
                ]);

                $this->info("   ✅ Migration marquée comme exécutée (batch: $newBatch)");
                $this->newLine();

                $this->info('4. Exécution des migrations restantes...');
                $this->call('migrate', ['--force' => true]);
            }
        } else {
            $this->warn('   ⚠️  La table settings n\'existe pas');
            $this->newLine();
            
            $this->info('3. Exécution normale des migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        $this->newLine();
        $this->info('✅ Opération terminée !');

        return 0;
    }
}

