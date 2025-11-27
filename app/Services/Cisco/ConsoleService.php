<?php

namespace App\Services\Cisco;

class ConsoleService extends BaseCiscoApiService
{
    /**
     * Obtenir toutes les clés console
     *
     * @param bool $showAll Si true, affiche toutes les consoles (admin uniquement)
     * @return array
     */
    public function getAllConsoleKeys(bool $showAll = false): array
    {
        $params = $showAll ? '?show_all=true' : '';
        return $this->get("/api/v0/keys/console{$params}");
    }

    /**
     * Obtenir toutes les clés VNC
     *
     * @param bool $showAll Si true, affiche tous les VNC (admin uniquement)
     * @return array
     */
    public function getAllVncKeys(bool $showAll = false): array
    {
        $params = $showAll ? '?show_all=true' : '';
        return $this->get("/api/v0/keys/vnc{$params}");
    }

    /**
     * Obtenir la clé VNC d'un node spécifique
     */
    public function getNodeVncKey(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/keys/vnc");
    }

    /**
     * Obtenir la clé console d'un node spécifique
     */
    public function getNodeConsoleKey(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/keys/console");
    }

    /**
     * Obtenir le log d'une console spécifique
     * Timeout augmenté à 30 secondes pour les logs qui peuvent être longs
     */
    public function getConsoleLog(string $labId, string $nodeId, string $consoleId): array
    {
        try {
            // NOTE IMPORTANTE: L'API CML 2.9.x attend un console_id comme ENTIER dans le path
            // Mais nous avons une clé console (UUID) qui est utilisée pour l'accès web.
            // Pour l'endpoint /log, CML utilise l'index 0 pour la console principale.
            // 
            // Solution: Utiliser 0 comme console_id (console principale par défaut)
            // La clé console (UUID) est uniquement pour l'accès web via /console/{key}
            
            $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $consoleId);
            
            // Utiliser 0 comme console_id (console principale par défaut)
            $consoleIdParam = 0;
            
            // Si consoleId est un entier (pas un UUID), l'utiliser directement
            if (is_numeric($consoleId) && !$isUuid) {
                $consoleIdParam = (int)$consoleId;
            }
            
            \Log::debug('Console: Récupération du log', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id_original' => $consoleId,
                'console_id_param' => $consoleIdParam,
                'is_uuid' => $isUuid,
            ]);
            
            // Utiliser un timeout plus long pour les logs console (30 secondes)
            $response = \Illuminate\Support\Facades\Http::withToken($this->getToken())
                ->withOptions([
                    'verify' => false,
                    'timeout' => 30, // Timeout augmenté pour les logs
                    'connect_timeout' => 10,
                ])
                ->get("{$this->baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleIdParam}/log");

            if (!$response->successful()) {
                $errorMessage = $response->body();
                $statusCode = $response->status();
                
                \Log::warning('Console: Erreur lors de la récupération du log', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'console_id' => $consoleId,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                ]);

                return [
                    'error' => "Erreur lors de la récupération du log: {$errorMessage}",
                    'status' => $statusCode,
                ];
            }

            $data = $response->json();
            
            // Normaliser la réponse si nécessaire
            if (is_string($data)) {
                return ['log' => $data];
            }
            
            return $data;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Console: Timeout ou erreur de connexion', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Timeout ou erreur de connexion au serveur CML. Le serveur ne répond pas dans les délais impartis.',
                'status' => 504,
                'is_timeout' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Console: Exception lors de la récupération du log', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Erreur lors de la récupération du log: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Obtenir toutes les consoles d'un node
     * Note: L'API CML n'a pas d'endpoint direct pour lister les consoles.
     * On construit la liste à partir des clés console disponibles.
     */
    public function getNodeConsoles(string $labId, string $nodeId): array
    {
        $consoles = [];
        
        // Essayer d'obtenir la clé console principale
        $consoleKey = $this->getNodeConsoleKey($labId, $nodeId);
        // Ignorer les erreurs 404 (console non disponible) - c'est normal pour certains nodes
        if (!isset($consoleKey['error']) && !empty($consoleKey) && (!isset($consoleKey['status']) || $consoleKey['status'] !== 404)) {
            // Si c'est une string (UUID), créer un objet console
            if (is_string($consoleKey)) {
                $consoles[] = [
                    'id' => $consoleKey,
                    'console_id' => $consoleKey,
                    'console_type' => 'console',
                    'protocol' => 'console',
                ];
            } elseif (is_array($consoleKey) && isset($consoleKey['id'])) {
                $consoles[] = array_merge([
                    'console_type' => 'console',
                    'protocol' => 'console',
                ], $consoleKey);
            }
        }
        
        // Essayer d'obtenir la clé VNC si disponible
        $vncKey = $this->getNodeVncKey($labId, $nodeId);
        // Ignorer les erreurs 404 (VNC non disponible) - c'est normal pour certains nodes
        if (!isset($vncKey['error']) && !empty($vncKey) && (!isset($vncKey['status']) || $vncKey['status'] !== 404)) {
            if (is_string($vncKey)) {
                $consoles[] = [
                    'id' => $vncKey,
                    'console_id' => $vncKey,
                    'console_type' => 'vnc',
                    'protocol' => 'vnc',
                ];
            } elseif (is_array($vncKey) && isset($vncKey['id'])) {
                $consoles[] = array_merge([
                    'console_type' => 'vnc',
                    'protocol' => 'vnc',
                ], $vncKey);
            }
        }
        
        // Retourner la structure attendue par le frontend
        $hasConsole = !empty(array_filter($consoles, function($c) {
            return ($c['console_type'] ?? '') === 'console';
        }));
        $hasVnc = !empty(array_filter($consoles, function($c) {
            return ($c['console_type'] ?? '') === 'vnc';
        }));
        
        return [
            'consoles' => $consoles,
            'available_types' => [
                'console' => $hasConsole,
                'vnc' => $hasVnc,
                'serial' => false, // Pas encore implémenté
            ],
        ];
    }

    /**
     * Obtenir les informations d'une console spécifique
     */
    public function getConsoleInfo(string $labId, string $nodeId, string $consoleId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}");
    }

    /**
     * Générer l'URL d'accès à la console web pour un node
     *
     * @param string $labId ID du lab
     * @param string $nodeId ID du node
     * @param string $consoleKey Clé de console obtenue via getNodeConsoleKey()
     * @return string URL de la console
     */
    public function getConsoleUrl(string $labId, string $nodeId, string $consoleKey): string
    {
        return "{$this->baseUrl}/console/{$consoleKey}";
    }

    /**
     * Générer l'URL d'accès VNC pour un node
     *
     * @param string $labId ID du lab
     * @param string $nodeId ID du node
     * @param string $vncKey Clé VNC obtenue via getNodeVncKey()
     * @return string URL VNC
     */
    public function getVncUrl(string $labId, string $nodeId, string $vncKey): string
    {
        return "{$this->baseUrl}/vnc/{$vncKey}";
    }

    /**
     * Obtenir les informations de session console
     *
     * @return array Liste des sessions console actives
     */
    public function getConsoleSessions(): array
    {
        return $this->get('/api/v0/console/sessions');
    }

    /**
     * Créer une session console interactive pour un node
     *
     * @param string $labId ID du lab
     * @param string $nodeId ID du node
     * @param array $options Options de connexion (type, protocole, etc.)
     * @return array Informations de la session créée
     */
    public function createConsoleSession(string $labId, string $nodeId, array $options = []): array
    {
        $data = array_merge([
            'lab_id' => $labId,
            'node_id' => $nodeId
        ], $options);

        \Log::info('Console: Tentative de création de session', [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'options' => $options,
            'data' => $data,
            'base_url' => $this->baseUrl,
            'has_token' => !empty($this->getToken()),
        ]);

        $result = $this->post('/api/v0/console/session', $data);

        \Log::info('Console: Résultat de création de session', [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'has_error' => isset($result['error']),
            'status' => $result['status'] ?? null,
            'result_keys' => array_keys($result),
        ]);

        return $result;
    }

    /**
     * Fermer une session console
     *
     * @param string $sessionId ID de la session à fermer
     * @return array Résultat de la fermeture
     */
    public function closeConsoleSession(string $sessionId): array
    {
        return $this->delete("/api/v0/console/session/{$sessionId}");
    }

    /**
     * Obtenir les types de console disponibles pour un node
     */
    public function getAvailableConsoleTypes(string $labId, string $nodeId): array
    {
        $node = $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}");

        // Si erreur lors de la récupération du node, retourner les types par défaut
        if (isset($node['error'])) {
            return [
                'serial' => false,
                'vnc' => false,
                'console' => true // Toujours disponible
            ];
        }

        return [
            'serial' => isset($node['configuration']['serial_devices']) && count($node['configuration']['serial_devices']) > 0,
            'vnc' => isset($node['vnc_key']) || ($node['node_definition'] ?? '') === 'desktop',
            'console' => true // Toujours disponible
        ];
    }

    /**
     * Obtenir l'URL de connexion SSH au terminal du contrôleur
     *
     * @param string $hostname Hostname ou IP du contrôleur
     * @param int $port Port SSH (défaut: 22)
     * @return string Commande SSH
     */
    public function getControllerSshCommand(string $hostname, int $port = 22): string
    {
        return $port === 22
            ? "ssh {$hostname}"
            : "ssh -p {$port} {$hostname}";
    }

    /**
     * Obtenir les statistiques des consoles actives
     */
    public function getConsoleStats(): array
    {
        return $this->get('/api/v0/console/stats');
    }
}

