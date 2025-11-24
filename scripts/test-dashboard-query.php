<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

echo "🧪 Test de la requête de durée moyenne\n";
echo "======================================\n\n";

$driver = DB::connection()->getDriverName();
echo "Driver de base de données: $driver\n\n";

try {
    if ($driver === 'sqlite') {
        echo "Test avec syntaxe SQLite (julianday)...\n";
        $result = Reservation::where('status', 'completed')
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->selectRaw('AVG((julianday(end_at) - julianday(start_at)) * 24 * 60) as avg_duration')
            ->first();
    } else {
        echo "Test avec syntaxe PostgreSQL (EXTRACT EPOCH)...\n";
        $result = Reservation::where('status', 'completed')
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (end_at - start_at)) / 60) as avg_duration')
            ->first();
    }
    
    $avgDuration = $result->avg_duration ?? 0;
    echo "✅ Requête exécutée avec succès!\n";
    echo "Durée moyenne: " . round($avgDuration, 2) . " minutes\n";
    
    if ($avgDuration == 0) {
        echo "ℹ️  Aucune réservation complétée trouvée (c'est normal si la base est vide)\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Test réussi!\n";

