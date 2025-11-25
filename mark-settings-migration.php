<?php

/**
 * Script simple pour marquer la migration settings comme exécutée
 * Usage: php mark-settings-migration.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$migrationName = '2025_11_17_114322_create_settings_table';

echo "🔧 Marquage de la migration settings\n";
echo "=====================================\n\n";

try {
    // Vérifier si la migration est déjà enregistrée
    $exists = DB::table('migrations')
        ->where('migration', $migrationName)
        ->exists();
    
    if ($exists) {
        echo "✅ La migration est déjà enregistrée\n";
    } else {
        echo "📝 Marquage de la migration comme exécutée...\n";
        
        // Obtenir le batch maximum
        $maxBatch = DB::table('migrations')->max('batch') ?? 0;
        $newBatch = $maxBatch + 1;
        
        // Insérer l'enregistrement de migration
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $newBatch,
        ]);
        
        echo "✅ Migration marquée comme exécutée (batch: $newBatch)\n";
    }
    
    echo "\n✅ Terminé !\n";
    echo "\n💡 Maintenant vous pouvez exécuter: php artisan migrate --force\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

