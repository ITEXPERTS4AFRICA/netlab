<?php
/**
 * Script de test pour vérifier le format des interfaces retournées par l'API CML
 * Usage: php test-interface-endpoint.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CiscoApiService;

// Récupérer les arguments (labId et nodeId)
$labId = $argv[1] ?? null;
$nodeId = $argv[2] ?? null;

if (!$labId || !$nodeId) {
    echo "Usage: php test-interface-endpoint.php <labId> <nodeId>\n";
    exit(1);
}

$cisco = app(CiscoApiService::class);

// Simuler une session avec token (vous devrez peut-être ajuster cela)
$token = session('cml_token');
if ($token) {
    $cisco->setToken($token);
} else {
    echo "⚠️  Aucun token CML en session. Utilisez 'php artisan tinker' pour tester avec un token.\n";
    echo "Ou connectez-vous d'abord via l'interface web.\n";
    exit(1);
}

echo "🔍 Test de récupération des interfaces...\n";
echo "Lab ID: {$labId}\n";
echo "Node ID: {$nodeId}\n\n";

try {
    $interfaces = $cisco->nodes->getNodeInterfaces($labId, $nodeId);
    
    echo "✅ Réponse reçue:\n";
    echo "Type: " . gettype($interfaces) . "\n";
    
    if (isset($interfaces['error'])) {
        echo "❌ Erreur: " . json_encode($interfaces['error'], JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }
    
    if (is_array($interfaces)) {
        echo "Nombre d'éléments: " . count($interfaces) . "\n";
        echo "\n📋 Structure des données:\n";
        echo json_encode($interfaces, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
        // Analyser la structure
        if (count($interfaces) > 0) {
            $firstKey = array_key_first($interfaces);
            $firstValue = $interfaces[$firstKey];
            
            echo "\n🔬 Analyse du premier élément:\n";
            echo "Clé: {$firstKey}\n";
            echo "Type de valeur: " . gettype($firstValue) . "\n";
            
            if (is_array($firstValue)) {
                echo "Clés disponibles: " . implode(', ', array_keys($firstValue)) . "\n";
            }
        }
    } else {
        echo "⚠️  Format inattendu: " . gettype($interfaces) . "\n";
        echo json_encode($interfaces, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}


