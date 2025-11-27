<?php

/**
 * Script pour afficher les exemples de réponses JSON des endpoints
 * de génération intelligente de commandes
 */

echo "📋 Exemples de Réponses JSON - Système de Génération Intelligente de Commandes\n";
echo "==============================================================================\n\n";

// 1. Réponse GET /api/labs/{labId}/commands/analyze
echo "1️⃣ GET /api/labs/{labId}/commands/analyze\n";
echo "==========================================\n\n";

$exampleAnalyze = [
    'lab_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    'total_nodes' => 3,
    'total_commands' => 15,
    'commands_by_node' => [
        'node-1-uuid' => [
            'node_id' => 'node-1-uuid',
            'node_label' => 'Router-1',
            'node_definition' => 'iosv',
            'commands' => [
                [
                    'command' => 'show version',
                    'description' => 'Afficher la version du système',
                    'category' => 'system',
                    'priority' => 1,
                ],
                [
                    'command' => 'show running-config',
                    'description' => 'Afficher la configuration en cours',
                    'category' => 'configuration',
                    'priority' => 2,
                ],
                [
                    'command' => 'show ip interface brief',
                    'description' => 'Afficher un résumé des interfaces IP',
                    'category' => 'interface',
                    'priority' => 3,
                ],
                [
                    'command' => 'show ip route',
                    'description' => 'Afficher la table de routage',
                    'category' => 'routing',
                    'priority' => 4,
                ],
                [
                    'command' => 'show ip ospf neighbor',
                    'description' => 'Afficher les voisins OSPF',
                    'category' => 'routing',
                    'priority' => 5,
                ],
            ],
        ],
        'node-2-uuid' => [
            'node_id' => 'node-2-uuid',
            'node_label' => 'Switch-1',
            'node_definition' => 'iosv-l2',
            'commands' => [
                [
                    'command' => 'show version',
                    'description' => 'Afficher la version du système',
                    'category' => 'system',
                    'priority' => 1,
                ],
                [
                    'command' => 'show vlan brief',
                    'description' => 'Afficher un résumé des VLANs',
                    'category' => 'vlan',
                    'priority' => 3,
                ],
                [
                    'command' => 'show interface status',
                    'description' => 'Afficher le statut des interfaces',
                    'category' => 'interface',
                    'priority' => 4,
                ],
                [
                    'command' => 'show spanning-tree',
                    'description' => 'Afficher l\'état du spanning tree',
                    'category' => 'switching',
                    'priority' => 5,
                ],
                [
                    'command' => 'show mac address-table',
                    'description' => 'Afficher la table d\'adresses MAC',
                    'category' => 'switching',
                    'priority' => 6,
                ],
            ],
        ],
    ],
];

echo "✅ Exemple de réponse JSON:\n\n";
echo json_encode($exampleAnalyze, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// 2. Réponse GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended
echo "2️⃣ GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended\n";
echo "==============================================================\n\n";

$exampleRecommended = [
    'lab_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    'node_id' => 'node-1-uuid',
    'node_label' => 'Router-1',
    'node_definition' => 'iosv',
    'commands' => [
        [
            'command' => 'show version',
            'description' => 'Afficher la version du système',
            'category' => 'system',
            'priority' => 1,
        ],
        [
            'command' => 'show running-config',
            'description' => 'Afficher la configuration en cours',
            'category' => 'configuration',
            'priority' => 2,
        ],
        [
            'command' => 'show ip interface brief',
            'description' => 'Afficher un résumé des interfaces IP',
            'category' => 'interface',
            'priority' => 3,
        ],
        [
            'command' => 'show ip route',
            'description' => 'Afficher la table de routage',
            'category' => 'routing',
            'priority' => 4,
        ],
        [
            'command' => 'show interface GigabitEthernet0/0/0',
            'description' => 'Afficher les détails de l\'interface GigabitEthernet0/0/0',
            'category' => 'interface',
            'priority' => 7,
        ],
    ],
    'total_commands' => 5,
];

echo "✅ Exemple de réponse JSON:\n\n";
echo json_encode($exampleRecommended, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// 3. Réponse GET /api/labs/{labId}/commands/script
echo "3️⃣ GET /api/labs/{labId}/commands/script\n";
echo "=========================================\n\n";

$exampleScript = [
    'lab_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    'script' => "! Script de configuration généré automatiquement\n! Lab ID: a1b2c3d4-e5f6-7890-abcd-ef1234567890\n! Généré le: 2025-01-15 10:30:00\n\n! Configuration pour: Router-1 (iosv)\n! Node ID: node-1-uuid\n\n! --- system ---\nshow version\nshow running-config\n\n! --- interface ---\nshow ip interface brief\nshow interface GigabitEthernet0/0/0\n\n! --- routing ---\nshow ip route\nshow ip ospf neighbor\n",
    'analysis' => [
        'lab_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        'total_nodes' => 3,
        'total_commands' => 15,
        'commands_by_node' => [
            // Structure similaire à analyzeLab
        ],
    ],
];

echo "✅ Exemple de réponse JSON:\n\n";
echo json_encode($exampleScript, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// 4. Réponse POST /api/labs/{labId}/nodes/{nodeId}/commands/execute
echo "4️⃣ POST /api/labs/{labId}/nodes/{nodeId}/commands/execute\n";
echo "==========================================================\n\n";

$exampleExecute = [
    'lab_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    'node_id' => 'node-1-uuid',
    'command' => 'show version',
    'category' => 'system',
    'instructions' => [
        'step_1' => 'La commande doit être tapée dans la console IOS',
        'step_2' => 'Utiliser le polling des logs pour récupérer les résultats',
        'step_3' => 'GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log',
    ],
    'note' => 'CML n\'expose pas d\'API REST pour exécuter des commandes CLI. La commande doit être tapée manuellement dans la console.',
];

echo "✅ Exemple de réponse JSON:\n\n";
echo json_encode($exampleExecute, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// 5. Réponses d'erreur
echo "5️⃣ Réponses d'Erreur\n";
echo "====================\n\n";

$exampleError = [
    'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
    'status' => 401,
];

echo "✅ Exemple de réponse d'erreur (401):\n\n";
echo json_encode($exampleError, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

$exampleError404 = [
    'error' => 'Aucune commande trouvée pour ce node',
    'node_id' => 'node-1-uuid',
];

echo "✅ Exemple de réponse d'erreur (404):\n\n";
echo json_encode($exampleError404, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

$exampleError500 = [
    'error' => 'Erreur lors de l\'analyse: Impossible de récupérer la topologie du lab',
    'status' => 500,
];

echo "✅ Exemple de réponse d'erreur (500):\n\n";
echo json_encode($exampleError500, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// 6. Résumé des structures
echo "6️⃣ Résumé des Structures JSON\n";
echo "==============================\n\n";

echo "📋 Tous les endpoints retournent du JSON avec les structures suivantes:\n\n";

echo "GET /api/labs/{labId}/commands/analyze:\n";
echo "  - lab_id: string\n";
echo "  - total_nodes: integer\n";
echo "  - total_commands: integer\n";
echo "  - commands_by_node: object\n";
echo "    - [node_id]: object\n";
echo "      - node_id: string\n";
echo "      - node_label: string\n";
echo "      - node_definition: string\n";
echo "      - commands: array\n";
echo "        - command: string\n";
echo "        - description: string\n";
echo "        - category: string\n";
echo "        - priority: integer\n\n";

echo "GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended:\n";
echo "  - lab_id: string\n";
echo "  - node_id: string\n";
echo "  - node_label: string\n";
echo "  - node_definition: string\n";
echo "  - commands: array (même structure que ci-dessus)\n";
echo "  - total_commands: integer\n\n";

echo "GET /api/labs/{labId}/commands/script:\n";
echo "  - lab_id: string\n";
echo "  - script: string (script de configuration complet)\n";
echo "  - analysis: object (même structure que analyzeLab)\n\n";

echo "POST /api/labs/{labId}/nodes/{nodeId}/commands/execute:\n";
echo "  - lab_id: string\n";
echo "  - node_id: string\n";
echo "  - command: string\n";
echo "  - category: string\n";
echo "  - instructions: object\n";
echo "    - step_1: string\n";
echo "    - step_2: string\n";
echo "    - step_3: string\n";
echo "  - note: string\n\n";

echo "✅ Documentation complète des réponses JSON!\n";


