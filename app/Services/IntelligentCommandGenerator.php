<?php

namespace App\Services;

use App\Services\CiscoApiService;

/**
 * Service pour générer automatiquement des commandes CLI intelligentes
 * basées sur la structure du lab (topologie, nodes, interfaces, liens)
 */
class IntelligentCommandGenerator
{
    protected CiscoApiService $cisco;

    public function __construct(CiscoApiService $cisco)
    {
        $this->cisco = $cisco;
    }

    /**
     * Analyser la structure du lab et générer des commandes adaptées
     */
    public function analyzeLabAndGenerateCommands(string $labId): array
    {
        $commands = [];
        
        try {
            // 1. Récupérer la topologie du lab
            $topology = $this->cisco->labs->getLabTopology($labId);
            $nodes = $this->cisco->nodes->getLabNodes($labId, true);
            $links = $this->cisco->links->getLabLinks($labId);
            
            if (isset($topology['error']) || isset($nodes['error'])) {
                return [
                    'error' => 'Impossible de récupérer la topologie du lab',
                    'commands' => []
                ];
            }
            
            // 2. Analyser chaque node et générer des commandes
            foreach ($nodes as $node) {
                $nodeId = $node['id'] ?? $node['node_id'] ?? null;
                $nodeDef = $node['node_definition'] ?? $node['definition'] ?? '';
                $label = $node['label'] ?? $node['name'] ?? '';
                
                if (!$nodeId) continue;
                
                // Générer des commandes selon le type de node
                $nodeCommands = $this->generateCommandsForNode($nodeId, $nodeDef, $label, $links, $topology);
                if (!empty($nodeCommands)) {
                    $commands[$nodeId] = [
                        'node_id' => $nodeId,
                        'node_label' => $label,
                        'node_definition' => $nodeDef,
                        'commands' => $nodeCommands,
                    ];
                }
            }
            
            return [
                'lab_id' => $labId,
                'total_nodes' => count($nodes),
                'total_commands' => array_sum(array_map(fn($c) => count($c['commands']), $commands)),
                'commands_by_node' => $commands,
            ];
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'analyse du lab', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'error' => 'Erreur lors de l\'analyse: ' . $e->getMessage(),
                'commands' => []
            ];
        }
    }

    /**
     * Générer des commandes pour un node spécifique
     */
    protected function generateCommandsForNode(
        string $nodeId,
        string $nodeDefinition,
        string $label,
        array $links,
        array $topology
    ): array {
        $commands = [];
        
        // Commandes de base pour tous les équipements
        $commands[] = [
            'command' => 'show version',
            'description' => 'Afficher la version du système',
            'category' => 'system',
            'priority' => 1,
        ];
        
        $commands[] = [
            'command' => 'show running-config',
            'description' => 'Afficher la configuration en cours',
            'category' => 'configuration',
            'priority' => 2,
        ];
        
        // Commandes spécifiques selon le type de node
        $nodeType = strtolower($nodeDefinition);
        
        // Routeurs
        if (stripos($nodeType, 'router') !== false || 
            stripos($nodeType, 'iosv') !== false ||
            stripos($nodeType, 'ios-xe') !== false) {
            $commands = array_merge($commands, $this->generateRouterCommands($nodeId, $links));
        }
        
        // Switches
        if (stripos($nodeType, 'switch') !== false || 
            stripos($nodeType, 'iosv-l2') !== false) {
            $commands = array_merge($commands, $this->generateSwitchCommands($nodeId, $links));
        }
        
        // Analyser les interfaces connectées
        $interfaces = $this->getNodeInterfaces($nodeId, $links);
        if (!empty($interfaces)) {
            $commands = array_merge($commands, $this->generateInterfaceCommands($interfaces));
        }
        
        // Analyser les protocoles de routage
        if ($this->hasRoutingProtocol($topology, $nodeId)) {
            $commands = array_merge($commands, $this->generateRoutingCommands($nodeId, $topology));
        }
        
        return $commands;
    }

    /**
     * Générer des commandes pour un routeur
     */
    protected function generateRouterCommands(string $nodeId, array $links): array
    {
        $commands = [];
        
        $commands[] = [
            'command' => 'show ip interface brief',
            'description' => 'Afficher un résumé des interfaces IP',
            'category' => 'interface',
            'priority' => 3,
        ];
        
        $commands[] = [
            'command' => 'show ip route',
            'description' => 'Afficher la table de routage',
            'category' => 'routing',
            'priority' => 4,
        ];
        
        $commands[] = [
            'command' => 'show ip ospf neighbor',
            'description' => 'Afficher les voisins OSPF',
            'category' => 'routing',
            'priority' => 5,
        ];
        
        $commands[] = [
            'command' => 'show ip eigrp neighbors',
            'description' => 'Afficher les voisins EIGRP',
            'category' => 'routing',
            'priority' => 5,
        ];
        
        return $commands;
    }

    /**
     * Générer des commandes pour un switch
     */
    protected function generateSwitchCommands(string $nodeId, array $links): array
    {
        $commands = [];
        
        $commands[] = [
            'command' => 'show vlan brief',
            'description' => 'Afficher un résumé des VLANs',
            'category' => 'vlan',
            'priority' => 3,
        ];
        
        $commands[] = [
            'command' => 'show interface status',
            'description' => 'Afficher le statut des interfaces',
            'category' => 'interface',
            'priority' => 4,
        ];
        
        $commands[] = [
            'command' => 'show spanning-tree',
            'description' => 'Afficher l\'état du spanning tree',
            'category' => 'switching',
            'priority' => 5,
        ];
        
        $commands[] = [
            'command' => 'show mac address-table',
            'description' => 'Afficher la table d\'adresses MAC',
            'category' => 'switching',
            'priority' => 6,
        ];
        
        return $commands;
    }

    /**
     * Obtenir les interfaces d'un node depuis les liens
     */
    protected function getNodeInterfaces(string $nodeId, array $links): array
    {
        $interfaces = [];
        
        foreach ($links as $link) {
            $nodeA = $link['node_a'] ?? $link['n1'] ?? null;
            $nodeB = $link['node_b'] ?? $link['n2'] ?? null;
            
            if ($nodeA === $nodeId) {
                $interfaces[] = [
                    'interface' => $link['interface1'] ?? $link['i1'] ?? 'unknown',
                    'connected_to' => $nodeB,
                ];
            } elseif ($nodeB === $nodeId) {
                $interfaces[] = [
                    'interface' => $link['interface2'] ?? $link['i2'] ?? 'unknown',
                    'connected_to' => $nodeA,
                ];
            }
        }
        
        return $interfaces;
    }

    /**
     * Générer des commandes pour les interfaces
     */
    protected function generateInterfaceCommands(array $interfaces): array
    {
        $commands = [];
        
        foreach ($interfaces as $iface) {
            $ifaceName = $iface['interface'] ?? 'unknown';
            
            // Extraire le numéro d'interface (ex: GigabitEthernet0/0/0 -> 0/0/0)
            if (preg_match('/(\d+\/\d+\/\d+|\d+\/\d+|\d+)/', $ifaceName, $matches)) {
                $ifaceNum = $matches[1];
                $ifaceType = preg_match('/GigabitEthernet|FastEthernet|Ethernet/', $ifaceName, $typeMatches) 
                    ? $typeMatches[0] 
                    : 'GigabitEthernet';
                
                $commands[] = [
                    'command' => "show interface {$ifaceType}{$ifaceNum}",
                    'description' => "Afficher les détails de l'interface {$ifaceType}{$ifaceNum}",
                    'category' => 'interface',
                    'priority' => 7,
                ];
            }
        }
        
        return $commands;
    }

    /**
     * Vérifier si le lab utilise un protocole de routage
     */
    protected function hasRoutingProtocol(array $topology, string $nodeId): bool
    {
        // Analyser la topologie pour détecter OSPF, EIGRP, etc.
        // Pour l'instant, on suppose qu'il y a du routage si plusieurs nodes sont connectés
        $nodes = $topology['nodes'] ?? [];
        return count($nodes) > 2;
    }

    /**
     * Générer des commandes de routage
     */
    protected function generateRoutingCommands(string $nodeId, array $topology): array
    {
        $commands = [];
        
        $commands[] = [
            'command' => 'show ip protocols',
            'description' => 'Afficher les protocoles de routage configurés',
            'category' => 'routing',
            'priority' => 8,
        ];
        
        $commands[] = [
            'command' => 'show ip ospf database',
            'description' => 'Afficher la base de données OSPF',
            'category' => 'routing',
            'priority' => 9,
        ];
        
        return $commands;
    }

    /**
     * Générer un script de configuration automatique basé sur la topologie
     */
    public function generateConfigurationScript(string $labId, array $options = []): array
    {
        $analysis = $this->analyzeLabAndGenerateCommands($labId);
        
        if (isset($analysis['error'])) {
            return $analysis;
        }
        
        $script = [];
        $script[] = '! Script de configuration généré automatiquement';
        $script[] = '! Lab ID: ' . $labId;
        $script[] = '! Généré le: ' . now()->toDateTimeString();
        $script[] = '';
        
        foreach ($analysis['commands_by_node'] as $nodeData) {
            $script[] = "! Configuration pour: {$nodeData['node_label']} ({$nodeData['node_definition']})";
            $script[] = "! Node ID: {$nodeData['node_id']}";
            $script[] = '';
            
            // Grouper les commandes par catégorie
            $commandsByCategory = [];
            foreach ($nodeData['commands'] as $cmd) {
                $category = $cmd['category'] ?? 'other';
                if (!isset($commandsByCategory[$category])) {
                    $commandsByCategory[$category] = [];
                }
                $commandsByCategory[$category][] = $cmd;
            }
            
            // Générer le script par catégorie
            foreach ($commandsByCategory as $category => $cmds) {
                $script[] = "! --- {$category} ---";
                foreach ($cmds as $cmd) {
                    $script[] = $cmd['command'];
                }
                $script[] = '';
            }
        }
        
        return [
            'lab_id' => $labId,
            'script' => implode("\n", $script),
            'analysis' => $analysis,
        ];
    }
}


