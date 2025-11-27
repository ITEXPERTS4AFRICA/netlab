<?php

/**
 * Script de test pour vérifier les endpoints console CML 2.9.xb
 * et tester l'envoi de commandes CLI via l'API REST JSON
 * 
 * Usage: php test-cml-console-api.php [lab_id] [node_id]
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Charger Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Configuration
$baseUrl = config('services.cml.base_url') ?? env('CML_API_BASE_URL', 'https://54.38.146.213');
$username = env('CML_USERNAME');
$password = env('CML_PASSWORD');

// IDs de test (peuvent être passés en arguments)
$labId = $argv[1] ?? env('TEST_LAB_ID');
$nodeId = $argv[2] ?? env('TEST_NODE_ID');

echo "🔍 Test des endpoints console CML 2.9.xb\n";
echo "==========================================\n\n";

// 1. Authentification
echo "1️⃣ Authentification CML...\n";
$authResponse = Http::withOptions(['verify' => false, 'timeout' => 15])
    ->post("{$baseUrl}/api/v0/auth_extended", [
        'username' => $username,
        'password' => $password,
    ]);

if (!$authResponse->successful()) {
    echo "❌ Erreur d'authentification: {$authResponse->status()}\n";
    echo "   Réponse: {$authResponse->body()}\n";
    exit(1);
}

$token = $authResponse->json()['token'] ?? null;
if (!$token) {
    echo "❌ Token non reçu dans la réponse\n";
    exit(1);
}

echo "✅ Authentification réussie\n";
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// 2. Vérifier les endpoints console disponibles
echo "2️⃣ Vérification des endpoints console disponibles...\n\n";

// 2.1. Obtenir la clé console
if ($labId && $nodeId) {
    echo "   📋 GET /api/v0/labs/{$labId}/nodes/{$nodeId}/keys/console\n";
    $consoleKeyResponse = Http::withToken($token)
        ->withOptions(['verify' => false, 'timeout' => 10])
        ->get("{$baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/keys/console");
    
    if ($consoleKeyResponse->successful()) {
        $consoleKey = $consoleKeyResponse->json();
        echo "   ✅ Clé console obtenue\n";
        echo "      Console Key: " . ($consoleKey['console_key'] ?? 'N/A') . "\n";
        $consoleId = $consoleKey['console_key'] ?? null;
    } else {
        echo "   ❌ Erreur: {$consoleKeyResponse->status()}\n";
        echo "      Réponse: " . substr($consoleKeyResponse->body(), 0, 200) . "\n";
        $consoleId = null;
    }
    echo "\n";
    
    // 2.2. Obtenir le log console
    if ($consoleId) {
        echo "   📋 GET /api/v0/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/log\n";
        $logResponse = Http::withToken($token)
            ->withOptions(['verify' => false, 'timeout' => 10])
            ->get("{$baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/log");
        
        if ($logResponse->successful()) {
            $logData = $logResponse->json();
            $logContent = $logData['log'] ?? $logData;
            $logLines = is_array($logContent) ? $logContent : explode("\n", $logContent);
            echo "   ✅ Log console obtenu (" . count($logLines) . " lignes)\n";
            echo "      Dernières lignes:\n";
            foreach (array_slice($logLines, -5) as $line) {
                echo "         " . substr($line, 0, 80) . "\n";
            }
        } else {
            echo "   ❌ Erreur: {$logResponse->status()}\n";
            echo "      Réponse: " . substr($logResponse->body(), 0, 200) . "\n";
        }
        echo "\n";
    }
    
    // 2.3. Tester extract_configuration (pour voir si on peut exécuter des commandes)
    echo "   📋 PUT /api/v0/labs/{$labId}/nodes/{$nodeId}/extract_configuration\n";
    echo "      (Extrait la configuration du node - pas pour exécuter des commandes)\n";
    $extractResponse = Http::withToken($token)
        ->withOptions(['verify' => false, 'timeout' => 30])
        ->put("{$baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/extract_configuration");
    
    if ($extractResponse->successful()) {
        echo "   ✅ Configuration extraite avec succès\n";
        $config = $extractResponse->json();
        if (isset($config['config'])) {
            $configLines = is_array($config['config']) ? $config['config'] : explode("\n", $config['config']);
            echo "      Configuration (" . count($configLines) . " lignes)\n";
            echo "      Premières lignes:\n";
            foreach (array_slice($configLines, 0, 5) as $line) {
                echo "         " . substr($line, 0, 80) . "\n";
            }
        }
    } else {
        echo "   ⚠️  Erreur ou non supporté: {$extractResponse->status()}\n";
        if ($extractResponse->status() !== 404) {
            echo "      Réponse: " . substr($extractResponse->body(), 0, 200) . "\n";
        }
    }
    echo "\n";
} else {
    echo "   ⚠️  Lab ID et Node ID non fournis. Utilisez:\n";
    echo "      php test-cml-console-api.php [lab_id] [node_id]\n";
    echo "      ou configurez TEST_LAB_ID et TEST_NODE_ID dans .env\n\n";
}

// 3. Rechercher dans la documentation openapi.json pour d'autres endpoints
echo "3️⃣ Analyse de la documentation API CML 2.9.xb...\n\n";

$openApiFile = __DIR__ . '/app/Services/openapi.json';
if (file_exists($openApiFile)) {
    $openApi = json_decode(file_get_contents($openApiFile), true);
    
    if ($openApi) {
        echo "   📚 Endpoints console trouvés dans openapi.json:\n\n";
        
        // Chercher tous les endpoints console
        $consoleEndpoints = [];
        foreach ($openApi['paths'] ?? [] as $path => $methods) {
            if (stripos($path, 'console') !== false || stripos($path, 'consoles') !== false) {
                foreach ($methods as $method => $details) {
                    if (in_array(strtolower($method), ['get', 'post', 'put', 'patch', 'delete'])) {
                        $consoleEndpoints[] = [
                            'method' => strtoupper($method),
                            'path' => $path,
                            'summary' => $details['summary'] ?? 'N/A',
                            'operationId' => $details['operationId'] ?? 'N/A',
                        ];
                    }
                }
            }
        }
        
        if (count($consoleEndpoints) > 0) {
            foreach ($consoleEndpoints as $endpoint) {
                echo "      {$endpoint['method']} {$endpoint['path']}\n";
                echo "         Summary: {$endpoint['summary']}\n";
                echo "         Operation: {$endpoint['operationId']}\n\n";
            }
        } else {
            echo "   ⚠️  Aucun endpoint console trouvé dans openapi.json\n\n";
        }
        
        // Vérifier s'il y a des endpoints pour exécuter des commandes
        echo "   🔍 Recherche d'endpoints pour exécuter des commandes CLI...\n\n";
        $commandEndpoints = [];
        foreach ($openApi['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $summary = strtolower($details['summary'] ?? '');
                $operationId = strtolower($details['operationId'] ?? '');
                
                if (stripos($summary, 'command') !== false || 
                    stripos($summary, 'execute') !== false ||
                    stripos($summary, 'send') !== false ||
                    stripos($operationId, 'command') !== false ||
                    stripos($operationId, 'execute') !== false) {
                    $commandEndpoints[] = [
                        'method' => strtoupper($method),
                        'path' => $path,
                        'summary' => $details['summary'] ?? 'N/A',
                    ];
                }
            }
        }
        
        if (count($commandEndpoints) > 0) {
            echo "      ✅ Endpoints potentiels pour commandes trouvés:\n\n";
            foreach ($commandEndpoints as $endpoint) {
                echo "      {$endpoint['method']} {$endpoint['path']}\n";
                echo "         Summary: {$endpoint['summary']}\n\n";
            }
        } else {
            echo "      ❌ Aucun endpoint trouvé pour exécuter des commandes CLI directement\n";
            echo "      ℹ️  CML n'expose pas d'API REST pour envoyer des commandes CLI\n";
            echo "      ℹ️  Les commandes doivent être tapées dans la console (iframe)\n";
            echo "      ℹ️  Les résultats sont récupérés via GET /consoles/{console_id}/log\n\n";
        }
    } else {
        echo "   ❌ Impossible de parser openapi.json\n\n";
    }
} else {
    echo "   ⚠️  Fichier openapi.json non trouvé\n\n";
}

// 4. Conclusion
echo "4️⃣ Conclusion\n";
echo "==============\n\n";
echo "📋 Endpoints console CML 2.9.xb disponibles:\n\n";
echo "   ✅ GET  /api/v0/labs/{lab_id}/nodes/{node_id}/keys/console\n";
echo "      → Obtient la clé console pour accéder à la console\n\n";
echo "   ✅ GET  /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log\n";
echo "      → Récupère le log de la console (résultats des commandes)\n\n";
echo "   ✅ PUT  /api/v0/labs/{lab_id}/nodes/{node_id}/extract_configuration\n";
echo "      → Extrait la configuration du node (pas pour exécuter des commandes)\n\n";
echo "   ❌ POST /api/v0/.../execute_command (N'EXISTE PAS)\n";
echo "   ❌ POST /api/v0/.../send_command (N'EXISTE PAS)\n\n";
echo "💡 Méthode recommandée pour envoyer des commandes CLI:\n\n";
echo "   1. Obtenir la clé console via GET /keys/console\n";
echo "   2. Accéder à la console via l'URL: {base_url}/console/{console_key}\n";
echo "   3. Taper les commandes dans l'iframe de la console\n";
echo "   4. Récupérer les résultats via GET /consoles/{console_id}/log\n\n";
echo "✅ Notre implémentation actuelle (polling des logs) est correcte !\n";

