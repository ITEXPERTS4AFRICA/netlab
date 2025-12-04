<?php

/**
 * Script de test pour interroger les endpoints de génération intelligente de commandes
 * et afficher les réponses JSON
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CiscoApiService;
use App\Services\IntelligentCommandGenerator;
use Illuminate\Support\Facades\Session;

echo "🧪 Test des Réponses JSON - Système de Génération Intelligente de Commandes\n";
echo "===========================================================================\n\n";

// 1. Configuration
$cisco = new CiscoApiService();
$token = Session::get('cml_token');

if (!$token) {
    echo "❌ Token CML non disponible. Veuillez vous connecter d'abord.\n";
    echo "   Utilisez: php artisan cml:auth ou connectez-vous via l'interface web.\n\n";
    exit(1);
}

$cisco->setToken($token);
echo "✅ Token CML disponible\n";
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// 2. Obtenir un lab pour tester
echo "2️⃣ Récupération d'un Lab pour Test\n";
echo "------------------------------------\n\n";

try {
    $labs = $cisco->labs->getLabs();
    if (isset($labs['error']) || empty($labs)) {
        echo "❌ Aucun lab disponible ou erreur: " . ($labs['error'] ?? 'Aucun lab') . "\n\n";
        exit(1);
    }
    
    // Prendre le premier lab
    $testLab = is_array($labs) ? ($labs[0] ?? null) : null;
    if (!$testLab || !is_array($testLab)) {
        echo "❌ Impossible de trouver un lab valide\n\n";
        exit(1);
    }
    
    $labId = $testLab['id'] ?? $testLab['lab_id'] ?? null;
    if (!$labId) {
        echo "❌ Lab ID non trouvé\n\n";
        exit(1);
    }
    
    echo "✅ Lab trouvé:\n";
    echo "   Lab ID: {$labId}\n";
    echo "   Titre: " . ($testLab['title'] ?? $testLab['lab_title'] ?? 'N/A') . "\n";
    echo "   État: " . ($testLab['state'] ?? 'N/A') . "\n\n";
    
    // Obtenir les nodes du lab
    $nodes = $cisco->nodes->getLabNodes($labId, true);
    if (isset($nodes['error']) || empty($nodes)) {
        echo "❌ Aucun node disponible ou erreur: " . ($nodes['error'] ?? 'Aucun node') . "\n\n";
        exit(1);
    }
    
    // Prendre le premier node
    $testNode = is_array($nodes) ? ($nodes[0] ?? null) : null;
    if (!$testNode || !is_array($testNode)) {
        echo "❌ Impossible de trouver un node valide\n\n";
        exit(1);
    }
    
    $nodeId = $testNode['id'] ?? $testNode['node_id'] ?? null;
    if (!$nodeId) {
        echo "❌ Node ID non trouvé\n\n";
        exit(1);
    }
    
    echo "✅ Node trouvé:\n";
    echo "   Node ID: {$nodeId}\n";
    echo "   Label: " . ($testNode['label'] ?? $testNode['name'] ?? 'N/A') . "\n";
    echo "   Définition: " . ($testNode['node_definition'] ?? 'N/A') . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur lors de la récupération du lab/node: {$e->getMessage()}\n\n";
    exit(1);
}

// 3. Tester les endpoints et afficher les réponses JSON
echo "3️⃣ Test des Endpoints et Réponses JSON\n";
echo "=======================================\n\n";

$generator = new IntelligentCommandGenerator($cisco);

// Test 1: Analyser le lab
echo "Test 1: GET /api/labs/{labId}/commands/analyze\n";
echo "------------------------------------------------\n";
try {
    $analysis = $generator->analyzeLabAndGenerateCommands($labId);
    
    echo "✅ Réponse JSON:\n\n";
    echo json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n\n";
    
    if (isset($analysis['error'])) {
        echo "❌ Erreur dans la réponse: {$analysis['error']}\n\n";
    } else {
        echo "📊 Résumé:\n";
        echo "   - Total nodes: " . ($analysis['total_nodes'] ?? 0) . "\n";
        echo "   - Total commandes: " . ($analysis['total_commands'] ?? 0) . "\n";
        echo "   - Nodes avec commandes: " . count($analysis['commands_by_node'] ?? []) . "\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
    echo "   Trace: " . substr($e->getTraceAsString(), 0, 200) . "...\n\n";
}

// Test 2: Obtenir les commandes recommandées pour un node
echo "Test 2: GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended\n";
echo "-------------------------------------------------------------------\n";
try {
    $analysis = $generator->analyzeLabAndGenerateCommands($labId);
    
    if (!isset($analysis['error']) && isset($analysis['commands_by_node'][$nodeId])) {
        $nodeCommands = $analysis['commands_by_node'][$nodeId];
        
        $response = [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'node_label' => $nodeCommands['node_label'],
            'node_definition' => $nodeCommands['node_definition'],
            'commands' => $nodeCommands['commands'],
            'total_commands' => count($nodeCommands['commands']),
        ];
        
        echo "✅ Réponse JSON:\n\n";
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n\n";
        
        echo "📊 Résumé:\n";
        echo "   - Node: {$response['node_label']} ({$response['node_definition']})\n";
        echo "   - Nombre de commandes: {$response['total_commands']}\n";
        echo "   - Catégories: " . implode(', ', array_unique(array_column($response['commands'], 'category'))) . "\n\n";
    } else {
        echo "⚠️  Aucune commande trouvée pour ce node\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n\n";
}

// Test 3: Générer un script de configuration
echo "Test 3: GET /api/labs/{labId}/commands/script\n";
echo "----------------------------------------------\n";
try {
    $script = $generator->generateConfigurationScript($labId);
    
    if (isset($script['error'])) {
        echo "❌ Erreur: {$script['error']}\n\n";
    } else {
        echo "✅ Réponse JSON (extrait):\n\n";
        
        // Afficher la structure JSON (sans le script complet qui peut être long)
        $responseStructure = [
            'lab_id' => $script['lab_id'],
            'script' => substr($script['script'], 0, 500) . "\n... (script tronqué, " . strlen($script['script']) . " caractères au total)",
            'analysis' => [
                'total_nodes' => $script['analysis']['total_nodes'] ?? 0,
                'total_commands' => $script['analysis']['total_commands'] ?? 0,
            ],
        ];
        
        echo json_encode($responseStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n\n";
        
        echo "📊 Résumé:\n";
        echo "   - Longueur du script: " . strlen($script['script']) . " caractères\n";
        echo "   - Total nodes: " . ($script['analysis']['total_nodes'] ?? 0) . "\n";
        echo "   - Total commandes: " . ($script['analysis']['total_commands'] ?? 0) . "\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n\n";
}

// Test 4: Préparer l'exécution d'une commande
echo "Test 4: POST /api/labs/{labId}/nodes/{nodeId}/commands/execute\n";
echo "----------------------------------------------------------------\n";
try {
    // Simuler une requête POST
    $command = 'show version';
    $category = 'system';
    
    $response = [
        'lab_id' => $labId,
        'node_id' => $nodeId,
        'command' => $command,
        'category' => $category,
        'instructions' => [
            'step_1' => 'La commande doit être tapée dans la console IOS',
            'step_2' => 'Utiliser le polling des logs pour récupérer les résultats',
            'step_3' => 'GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log',
        ],
        'note' => 'CML n\'expose pas d\'API REST pour exécuter des commandes CLI. La commande doit être tapée manuellement dans la console.',
    ];
    
    echo "✅ Réponse JSON:\n\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n\n";
    
    echo "📝 Note: Cette réponse indique que CML n'a pas d'API directe pour exécuter des commandes.\n";
    echo "   La commande doit être tapée dans la console IOS et les résultats récupérés via polling.\n\n";
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n\n";
}

// 4. Résumé des structures JSON
echo "4️⃣ Résumé des Structures JSON\n";
echo "==============================\n\n";

echo "📋 Structure de réponse pour GET /api/labs/{labId}/commands/analyze:\n";
echo json_encode([
    'lab_id' => 'string',
    'total_nodes' => 'integer',
    'total_commands' => 'integer',
    'commands_by_node' => [
        'node_id' => [
            'node_id' => 'string',
            'node_label' => 'string',
            'node_definition' => 'string',
            'commands' => [
                [
                    'command' => 'string',
                    'description' => 'string',
                    'category' => 'string',
                    'priority' => 'integer',
                ],
            ],
        ],
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "📋 Structure de réponse pour GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended:\n";
echo json_encode([
    'lab_id' => 'string',
    'node_id' => 'string',
    'node_label' => 'string',
    'node_definition' => 'string',
    'commands' => [
        [
            'command' => 'string',
            'description' => 'string',
            'category' => 'string',
            'priority' => 'integer',
        ],
    ],
    'total_commands' => 'integer',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "📋 Structure de réponse pour GET /api/labs/{labId}/commands/script:\n";
echo json_encode([
    'lab_id' => 'string',
    'script' => 'string (script de configuration)',
    'analysis' => [
        'lab_id' => 'string',
        'total_nodes' => 'integer',
        'total_commands' => 'integer',
        'commands_by_node' => 'object',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "📋 Structure de réponse pour POST /api/labs/{labId}/nodes/{nodeId}/commands/execute:\n";
echo json_encode([
    'lab_id' => 'string',
    'node_id' => 'string',
    'command' => 'string',
    'category' => 'string',
    'instructions' => [
        'step_1' => 'string',
        'step_2' => 'string',
        'step_3' => 'string',
    ],
    'note' => 'string',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "✅ Tests terminés!\n";


