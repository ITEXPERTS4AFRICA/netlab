<?php

/**
 * Script PHP pour résoudre le problème de migration settings
 * Usage: php fix-settings-migration.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 Correction de la migration settings\n";
echo str_repeat("=", 50) . "\n\n";

$migrationName = '2025_11_17_114322_create_settings_table';

try {
    // Vérifier si la table settings existe
    echo "1. Vérification de la table settings...\n";
    if (Schema::hasTable('settings')) {
        echo "   ✅ La table settings existe déjà\n\n";
        
        // Vérifier si la migration est enregistrée
        echo "2. Vérification de l'enregistrement de la migration...\n";
        $migrationExists = DB::table('migrations')
            ->where('migration', $migrationName)
            ->exists();
        
        if ($migrationExists) {
            echo "   ✅ La migration est déjà enregistrée\n\n";
            echo "3. Exécution des migrations restantes...\n";
            exec('php artisan migrate --force', $output, $returnCode);
            foreach ($output as $line) {
                echo "   $line\n";
            }
        } else {
            echo "   ⚠️  La migration n'est pas enregistrée\n\n";
            echo "3. Marquage de la migration comme exécutée...\n";
            
            // Obtenir le batch maximum
            $maxBatch = DB::table('migrations')->max('batch') ?? 0;
            $newBatch = $maxBatch + 1;
            
            // Insérer l'enregistrement de migration
            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $newBatch,
            ]);
            
            echo "   ✅ Migration marquée comme exécutée (batch: $newBatch)\n\n";
            echo "4. Exécution des migrations restantes...\n";
            exec('php artisan migrate --force', $output, $returnCode);
            foreach ($output as $line) {
                echo "   $line\n";
            }
        }
    } else {
        echo "   ⚠️  La table settings n'existe pas\n\n";
        echo "3. Exécution normale des migrations...\n";
        exec('php artisan migrate --force', $output, $returnCode);
        foreach ($output as $line) {
            echo "   $line\n";
        }
    }
    
    echo "\n✅ Opération terminée avec succès !\n";
    exit(0);
} catch (\Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . substr($e->getTraceAsString(), 0, 500) . "...\n";
    exit(1);
}

