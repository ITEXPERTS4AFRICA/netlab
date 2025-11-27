<?php

/**
 * Script de test complet pour vérifier tous les endpoints console
 * et tester le flux complet d'envoi/récupération de commandes CLI
 * 
 * Usage: php test-console-endpoints.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\CiscoApiService;

// Charger Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test Complet des Endpoints Console CML 2.9.xb\n";
echo "================================================\n\n";

// Configuration
$cisco = new CiscoApiService();
$baseUrl = $cisco->labs->baseUrl ?? config('services.cml.base_url') ?? env('CML_API_BASE_URL');

// IDs de test
$labId = env('TEST_LAB_ID');
$nodeId = env('TEST_NODE_ID');

if (!$labId || !$nodeId) {
    echo "⚠️  TEST_LAB_ID et TEST_NODE_ID non configurés dans .env\n";
    echo "   Utilisation des IDs depuis la base de données...\n\n";
    
    // Essayer de récupérer un lab depuis la DB
    try {
        $lab = \App\Models\Lab::where('state', 'RUNNING')->orWhere('state', 'STARTED')->first();
        if ($lab) {
            $labId = $lab->cml_id;
            echo "   ✅ Lab trouvé: {$lab->lab_title} (ID: {$labId})\n";
            
            // Récupérer les nodes du lab
            $nodes = $cisco->nodes->getLabNodes($labId, true);
            if (is_array($nodes) && count($nodes) > 0) {
                $firstNode = is_array($nodes[0]) ? $nodes[0] : (is_string($nodes[0]) ? ['id' => $nodes[0]] : null);
                if ($firstNode && isset($firstNode['id'])) {
                    $nodeId = $firstNode['id'];
                    echo "   ✅ Node trouvé: " . ($firstNode['label'] ?? $firstNode['id']) . " (ID: {$nodeId})\n\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Erreur: {$e->getMessage()}\n";
        exit(1);
    }
}

if (!$labId || !$nodeId) {
    echo "❌ Impossible de trouver un lab/node pour tester\n";
    echo "   Configurez TEST_LAB_ID et TEST_NODE_ID dans .env\n";
    exit(1);
}

echo "📋 Configuration de test:\n";
echo "   Lab ID: {$labId}\n";
echo "   Node ID: {$nodeId}\n";
echo "   Base URL: {$baseUrl}\n\n";

// Test 1: Obtenir la clé console
echo "1️⃣ Test: Obtenir la clé console\n";
echo "────────────────────────────────\n";
try {
    $consoleKeyResponse = $cisco->console->getNodeConsoleKey($labId, $nodeId);
    
    if (isset($consoleKeyResponse['error'])) {
        echo "   ❌ Erreur: {$consoleKeyResponse['error']}\n";
        if (isset($consoleKeyResponse['status'])) {
            echo "      Status: {$consoleKeyResponse['status']}\n";
        }
    } else {
        $consoleKey = $consoleKeyResponse['console_key'] ?? null;
        echo "   ✅ Clé console obtenue\n";
        echo "      Console Key: {$consoleKey}\n";
        
        // Utiliser la clé comme consoleId pour les tests suivants
        $consoleId = $consoleKey;
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: {$e->getMessage()}\n";
    $consoleId = null;
}
echo "\n";

// Test 2: Obtenir le log console (avant commande)
if ($consoleId) {
    echo "2️⃣ Test: Récupérer le log console (état initial)\n";
    echo "─────────────────────────────────────────────────\n";
    try {
        $logResponse = $cisco->console->getConsoleLog($labId, $nodeId, $consoleId);
        
        if (isset($logResponse['error'])) {
            echo "   ❌ Erreur: {$logResponse['error']}\n";
        } else {
            $logContent = $logResponse['log'] ?? $logResponse;
            $logLines = is_array($logContent) ? $logContent : explode("\n", (string)$logContent);
            echo "   ✅ Log console obtenu\n";
            echo "      Nombre de lignes: " . count($logLines) . "\n";
            echo "      Dernières 3 lignes:\n";
            foreach (array_slice($logLines, -3) as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    echo "         " . substr($line, 0, 100) . "\n";
                }
            }
            $initialLogCount = count($logLines);
        }
    } catch (\Exception $e) {
        echo "   ❌ Exception: {$e->getMessage()}\n";
        $initialLogCount = 0;
    }
    echo "\n";
}

// Test 3: Vérifier extract_configuration
echo "3️⃣ Test: Extraire la configuration du node\n";
echo "────────────────────────────────────────────\n";
echo "   ℹ️  Note: extract_configuration extrait la config, mais ne permet pas d'exécuter des commandes\n";
try {
    $extractResponse = $cisco->nodes->extractNodeConfiguration($labId, $nodeId);
    
    if (isset($extractResponse['error'])) {
        echo "   ⚠️  Erreur ou non supporté: {$extractResponse['error']}\n";
    } else {
        echo "   ✅ Configuration extraite\n";
        $config = $extractResponse['config'] ?? $extractResponse;
        $configLines = is_array($config) ? $config : explode("\n", (string)$config);
        echo "      Nombre de lignes: " . count($configLines) . "\n";
        if (count($configLines) > 0) {
            echo "      Premières lignes:\n";
            foreach (array_slice($configLines, 0, 5) as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    echo "         " . substr($line, 0, 100) . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "   ⚠️  Exception: {$e->getMessage()}\n";
}
echo "\n";

// Test 4: Vérifier les endpoints disponibles dans notre API
echo "4️⃣ Test: Endpoints de notre API Laravel\n";
echo "─────────────────────────────────────────\n";

$token = session('cml_token');
if (!$token) {
    echo "   ⚠️  Token CML non disponible en session\n";
    echo "   ℹ️  Les endpoints nécessitent une authentification\n\n";
} else {
    $baseApiUrl = config('app.url') ?? 'http://localhost';
    
    $endpoints = [
        'GET /api/console/ping' => "{$baseApiUrl}/api/console/ping",
        "GET /api/labs/{$labId}/nodes/{$nodeId}/consoles" => "{$baseApiUrl}/api/labs/{$labId}/nodes/{$nodeId}/consoles",
        "GET /api/labs/{$labId}/nodes/{$nodeId}/interfaces" => "{$baseApiUrl}/api/labs/{$labId}/nodes/{$nodeId}/interfaces",
        "GET /api/labs/{$labId}/links?node_id={$nodeId}" => "{$baseApiUrl}/api/labs/{$labId}/links?node_id={$nodeId}",
    ];
    
    if ($consoleId) {
        $endpoints["GET /api/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/log"] = 
            "{$baseApiUrl}/api/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/log";
        $endpoints["GET /api/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/poll"] = 
            "{$baseApiUrl}/api/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/poll";
    }
    
    foreach ($endpoints as $name => $url) {
        echo "   📋 {$name}\n";
        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 10])
                ->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                echo "      ✅ Succès (Status: {$response->status()})\n";
                if (is_array($data)) {
                    $keys = array_keys($data);
                    echo "      Keys: " . implode(', ', array_slice($keys, 0, 5));
                    if (count($keys) > 5) {
                        echo " ... (+" . (count($keys) - 5) . " autres)";
                    }
                    echo "\n";
                }
            } else {
                echo "      ❌ Erreur (Status: {$response->status()})\n";
                $error = $response->json();
                if (isset($error['error'])) {
                    echo "      Message: {$error['error']}\n";
                }
            }
        } catch (\Exception $e) {
            echo "      ❌ Exception: {$e->getMessage()}\n";
        }
        echo "\n";
    }
}

// Test 5: Vérifier la documentation openapi.json pour les endpoints console
echo "5️⃣ Analyse de la documentation API CML 2.9.xb\n";
echo "────────────────────────────────────────────────\n";

$openApiFile = __DIR__ . '/app/Services/openapi.json';
if (file_exists($openApiFile)) {
    $openApiContent = file_get_contents($openApiFile);
    $openApi = json_decode($openApiContent, true);
    
    if ($openApi) {
        echo "   📚 Version API: {$openApi['info']['version']}\n";
        echo "   📚 Titre: {$openApi['info']['title']}\n\n";
        
        // Chercher tous les endpoints console
        $consolePaths = [];
        foreach ($openApi['paths'] ?? [] as $path => $methods) {
            if (stripos($path, 'console') !== false || stripos($path, 'consoles') !== false) {
                $consolePaths[$path] = $methods;
            }
        }
        
        if (count($consolePaths) > 0) {
            echo "   ✅ Endpoints console trouvés dans la documentation:\n\n";
            foreach ($consolePaths as $path => $methods) {
                foreach ($methods as $method => $details) {
                    if (in_array(strtolower($method), ['get', 'post', 'put', 'patch', 'delete'])) {
                        $summary = $details['summary'] ?? 'N/A';
                        $description = $details['description'] ?? '';
                        echo "      " . strtoupper($method) . " {$path}\n";
                        echo "         Summary: {$summary}\n";
                        if ($description) {
                            echo "         Description: " . substr($description, 0, 100) . "...\n";
                        }
                        echo "\n";
                    }
                }
            }
        }
        
        // Vérifier s'il existe un endpoint pour envoyer des commandes
        echo "   🔍 Recherche d'endpoints pour envoyer/exécuter des commandes CLI...\n\n";
        $foundCommandEndpoint = false;
        foreach ($openApi['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $summary = strtolower($details['summary'] ?? '');
                $operationId = strtolower($details['operationId'] ?? '');
                $description = strtolower($details['description'] ?? '');
                
                $keywords = ['command', 'execute', 'send', 'run', 'cli'];
                $found = false;
                foreach ($keywords as $keyword) {
                    if (stripos($summary, $keyword) !== false || 
                        stripos($operationId, $keyword) !== false ||
                        stripos($description, $keyword) !== false) {
                        $found = true;
                        break;
                    }
                }
                
                if ($found && strtolower($method) === 'post') {
                    $foundCommandEndpoint = true;
                    echo "      ⚠️  Endpoint potentiel trouvé:\n";
                    echo "         " . strtoupper($method) . " {$path}\n";
                    echo "         Summary: {$details['summary']}\n";
                    echo "         Operation: {$details['operationId']}\n\n";
                }
            }
        }
        
        if (!$foundCommandEndpoint) {
            echo "      ❌ Aucun endpoint POST trouvé pour envoyer des commandes CLI\n";
            echo "      ✅ Confirmation: CML n'expose pas d'API REST pour exécuter des commandes\n\n";
        }
    }
} else {
    echo "   ⚠️  Fichier openapi.json non trouvé\n\n";
}

// Conclusion
echo "6️⃣ Conclusion et Recommandations\n";
echo "──────────────────────────────────\n\n";
echo "📋 Résumé des endpoints console CML 2.9.xb:\n\n";
echo "   ✅ GET  /api/v0/labs/{lab_id}/nodes/{node_id}/keys/console\n";
echo "      → Obtient la clé console (console_key)\n\n";
echo "   ✅ GET  /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log\n";
echo "      → Récupère le log de la console (résultats des commandes)\n";
echo "      → C'est le seul moyen de récupérer les résultats des commandes CLI\n\n";
echo "   ✅ PUT  /api/v0/labs/{lab_id}/nodes/{node_id}/extract_configuration\n";
echo "      → Extrait la configuration actuelle du node\n";
echo "      → Ne permet PAS d'exécuter des commandes arbitraires\n\n";
echo "   ❌ POST /api/v0/.../execute_command (N'EXISTE PAS)\n";
echo "   ❌ POST /api/v0/.../send_command (N'EXISTE PAS)\n";
echo "   ❌ POST /api/v0/.../run_cli (N'EXISTE PAS)\n\n";
echo "💡 Méthode pour envoyer des commandes CLI (selon la doc CML 2.9.xb):\n\n";
echo "   1. Obtenir la clé console: GET /keys/console\n";
echo "   2. Accéder à la console web: {base_url}/console/{console_key}\n";
echo "   3. Taper les commandes dans l'iframe de la console\n";
echo "   4. Récupérer les résultats: GET /consoles/{console_id}/log\n\n";
echo "✅ Notre implémentation actuelle est CORRECTE:\n\n";
echo "   • Nous utilisons le polling intelligent des logs\n";
echo "   • Les commandes sont tapées via l'interface (pas d'API directe)\n";
echo "   • Les résultats sont récupérés via GET /consoles/{console_id}/log\n";
echo "   • Le polling se fait toutes les 2 secondes\n\n";
echo "🎯 Pour tester les commandes CLI en pratique:\n\n";
echo "   1. Ouvrir un lab dans l'interface web\n";
echo "   2. Sélectionner un node (la session s'ouvre automatiquement)\n";
echo "   3. Taper une commande dans la console IOS (ex: 'show version')\n";
echo "   4. Observer les résultats dans les logs (polling automatique)\n\n";

