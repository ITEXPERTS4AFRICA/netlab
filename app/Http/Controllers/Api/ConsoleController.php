<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsoleController extends Controller
{
    /**
     * Lister les consoles disponibles pour un nœud donné.
     * Cache: 20 secondes (consoles changent rarement)
     */
    public function index(string $labId, string $nodeId, CiscoApiService $cisco): JsonResponse
    {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        \Log::info('Console: Récupération des consoles', [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'has_token' => !empty($token),
        ]);

        if (!$token) {
            \Log::error('Console: Token CML non disponible');
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        // Cache pour éviter les appels répétés
        $cacheKey = 'api:console:index:' . md5($labId . $nodeId . $token);

        try {
        $consoles = \Illuminate\Support\Facades\Cache::remember($cacheKey, 20, function() use ($cisco, $labId, $nodeId) {
            return $cisco->console->getNodeConsoles($labId, $nodeId);
        });

            \Log::info('Console: Réponse getNodeConsoles', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'has_error' => isset($consoles['error']),
                'response_type' => gettype($consoles),
                'is_array' => is_array($consoles),
                'response_keys' => is_array($consoles) ? array_keys($consoles) : null,
            ]);

            if (isset($consoles['error'])) {
                // En cas d'erreur, invalider le cache
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                \Log::warning('Console: Erreur lors de la récupération des consoles', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'error' => $consoles['error'],
                    'status' => $consoles['status'] ?? null,
                    'endpoint' => "/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles",
            ]);
            return response()->json($consoles, $consoles['status'] ?? 500);
        }

            // La méthode getNodeConsoles retourne déjà la structure attendue avec 'consoles' et 'available_types'
            if (!is_array($consoles)) {
                \Log::warning('Console: Réponse invalide (pas un tableau)', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'response_type' => gettype($consoles),
                ]);
                $consoles = [
                    'consoles' => [],
                    'available_types' => [
                        'serial' => false,
                        'vnc' => false,
                        'console' => true,
                    ],
                ];
            } elseif (!isset($consoles['consoles']) || !isset($consoles['available_types'])) {
                // Si la structure n'est pas correcte, normaliser
                $consolesArray = isset($consoles['consoles']) ? $consoles['consoles'] : (is_array($consoles) && isset($consoles[0]) ? $consoles : []);
                $types = isset($consoles['available_types']) ? $consoles['available_types'] : [
                    'serial' => false,
                    'vnc' => false,
                    'console' => true,
                ];
                $consoles = [
                    'consoles' => $consolesArray,
                    'available_types' => $types,
                ];
            }

            \Log::info('Console: Réponse finale', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'consoles_count' => count($consoles['consoles'] ?? []),
                'available_types' => $consoles['available_types'] ?? [],
            ]);

            return response()->json([
                'consoles' => $consoles['consoles'] ?? [],
                'available_types' => $consoles['available_types'] ?? [
                    'serial' => false,
                    'vnc' => false,
                    'console' => true,
                ],
            ])->header('Cache-Control', 'public, max-age=20');
        } catch (\Exception $e) {
            \Log::error('Console: Exception lors de la récupération des consoles', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

        return response()->json([
                'error' => 'Erreur lors de la récupération des consoles: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => "/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles",
            ], 500);
        }
    }

    /**
     * Créer une session console pour un nœud.
     */
    public function store(Request $request, CiscoApiService $cisco): JsonResponse
    {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        $payload = $request->validate([
            'lab_id' => ['required', 'string'],
            'node_id' => ['required', 'string'],
            'type' => ['nullable', 'string'],
            'protocol' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
        ]);

        \Log::info('Console: Création d\'une session console', [
            'lab_id' => $payload['lab_id'],
            'node_id' => $payload['node_id'],
            'type' => $payload['type'] ?? null,
            'has_token' => !empty($token),
        ]);

        $options = [];

        if (isset($payload['type'])) {
            $options['type'] = $payload['type'];
        }

        if (isset($payload['protocol'])) {
            $options['protocol'] = $payload['protocol'];
        }

        if (isset($payload['options'])) {
            $options = array_merge($options, $payload['options']);
        }

        // CML n'a pas d'endpoint pour créer des sessions console.
        // Au lieu de cela, on obtient la clé console et on retourne l'URL d'accès.
        $consoleType = $payload['type'] ?? 'console';
        
        if ($consoleType === 'vnc') {
            $keyResponse = $cisco->console->getNodeVncKey($payload['lab_id'], $payload['node_id']);
        } else {
            $keyResponse = $cisco->console->getNodeConsoleKey($payload['lab_id'], $payload['node_id']);
        }

        if (isset($keyResponse['error'])) {
            $errorMessage = $keyResponse['error'];
            $errorDetail = $keyResponse['body'] ?? $keyResponse['detail'] ?? null;
            
            \Log::error('Console: Erreur lors de la récupération de la clé console', [
                'lab_id' => $payload['lab_id'],
                'node_id' => $payload['node_id'],
                'type' => $consoleType,
                'error' => $errorMessage,
                'status' => $keyResponse['status'] ?? null,
                'body' => $errorDetail,
            ]);
            
            $fullErrorMessage = $errorMessage;
            if ($errorDetail) {
                if (is_string($errorDetail)) {
                    $fullErrorMessage .= ': ' . $errorDetail;
                } elseif (is_array($errorDetail) && isset($errorDetail['description'])) {
                    $fullErrorMessage .= ': ' . $errorDetail['description'];
                }
            }
            
            return response()->json([
                'error' => $fullErrorMessage,
                'status' => $keyResponse['status'] ?? 500,
                'detail' => $keyResponse,
            ], $keyResponse['status'] ?? 500);
        }

        // Extraire la clé console (peut être une string ou un array avec 'id', 'key', ou 'data')
        $consoleKey = null;
        if (is_string($keyResponse)) {
            $consoleKey = $keyResponse;
        } elseif (is_array($keyResponse)) {
            // L'API CML peut retourner la clé dans différents champs
            $consoleKey = $keyResponse['id'] 
                ?? $keyResponse['key'] 
                ?? $keyResponse['data'] 
                ?? $keyResponse['console_key']
                ?? null;
        }
        
        if (!$consoleKey) {
            \Log::error('Console: Clé console introuvable dans la réponse', [
                'lab_id' => $payload['lab_id'],
                'node_id' => $payload['node_id'],
                'type' => $consoleType,
                'response' => $keyResponse,
                'response_type' => gettype($keyResponse),
                'response_keys' => is_array($keyResponse) ? array_keys($keyResponse) : null,
            ]);
            
            return response()->json([
                'error' => 'Clé console introuvable. Le node peut ne pas avoir de console disponible.',
                'status' => 404,
                'detail' => [
                    'response' => $keyResponse,
                    'response_type' => gettype($keyResponse),
                ],
            ], 404);
        }

        // Construire l'URL de la console
        $baseUrl = \App\Helpers\CmlConfigHelper::getBaseUrl();
        if ($consoleType === 'vnc') {
            $consoleUrl = "{$baseUrl}/vnc/{$consoleKey}";
        } else {
            // Format pour CML 2.x : essayer plusieurs formats possibles
            // Format 1: /console/{console_key} (format standard)
            // Format 2: /console/?id={console_key} (format avec query param)
            // Format 3: /console/{console_key} (sans slash)
            // On retourne le format standard, le frontend peut essayer les autres si nécessaire
            $consoleUrl = "{$baseUrl}/console/{$consoleKey}";
            
            \Log::info('Console: URL générée', [
                'base_url' => $baseUrl,
                'console_key' => $consoleKey,
                'console_url' => $consoleUrl,
                'note' => 'Si 404, essayer /console/?id={key} ou vérifier la documentation CML',
            ]);
        }

        \Log::info('Console: Clé console obtenue avec succès', [
            'lab_id' => $payload['lab_id'],
            'node_id' => $payload['node_id'],
            'type' => $consoleType,
            'console_key' => $consoleKey,
            'console_url' => $consoleUrl,
        ]);

            // NOTE: CML ne semble pas exposer de WebSocket fonctionnel pour les consoles
            // Les tests montrent que wss://{host}/console/ws?id={key} échoue systématiquement
            // On utilise donc uniquement l'approche iframe + polling qui fonctionne
            // Si un jour CML expose un WebSocket, décommenter les lignes ci-dessous :
            // $wsBaseUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $baseUrl);
            // $wsHref = "{$wsBaseUrl}/console/ws?id={$consoleKey}";

            // Retourner une réponse compatible avec l'ancien format de session
            // SANS ws_href pour forcer l'utilisation de l'iframe
            return response()->json([
                'session_id' => $consoleKey,
                'id' => $consoleKey,
                'console_id' => $consoleKey,
                'console_key' => $consoleKey,
                'console_url' => $consoleUrl,
                'url' => $consoleUrl,
                'lab_id' => $payload['lab_id'],
                'node_id' => $payload['node_id'],
                'type' => $consoleType,
                'protocol' => $consoleType === 'vnc' ? 'vnc' : 'console',
                // ws_href désactivé car CML ne l'expose pas
                // 'ws_href' => null,
            ]);
    }

    /**
     * Récupérer les sessions console actives.
     */
    public function sessions(CiscoApiService $cisco): JsonResponse
    {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        $sessions = $cisco->console->getConsoleSessions();

        if (isset($sessions['error'])) {
            \Log::warning('Console: Erreur lors de la récupération des sessions', [
                'error' => $sessions['error'],
                'has_token' => !empty($token),
            ]);
            return response()->json($sessions, $sessions['status'] ?? 500);
        }

        return response()->json($sessions);
    }

    /**
     * Fermer une session console.
     * Note: CML n'a pas d'endpoint pour fermer les sessions console.
     * Les sessions se ferment automatiquement après expiration de la clé.
     */
    public function destroy(string $sessionId, CiscoApiService $cisco): JsonResponse
    {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        \Log::info('Console: Fermeture d\'une session console', [
            'session_id' => $sessionId,
            'has_token' => !empty($token),
        ]);

        // CML n'a pas d'endpoint pour fermer les sessions console
        // Les sessions se ferment automatiquement après expiration de la clé
        // On retourne simplement un succès
        \Log::info('Console: Session fermée (côté client)', [
            'session_id' => $sessionId,
            'note' => 'CML n\'a pas d\'endpoint pour fermer les sessions, elles expirent automatiquement',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session fermée côté client. La session CML expirera automatiquement.',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Obtenir le log d'une console spécifique.
     */
    public function log(
        string $labId,
        string $nodeId,
        string $consoleId,
        CiscoApiService $cisco
    ): JsonResponse {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            \Log::error('Console: Token CML non disponible pour récupération du log', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
            ]);
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        \Log::info('Console: Tentative de récupération du log', [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'console_id' => $consoleId,
        ]);

        // Valider les paramètres
        if (empty($labId) || empty($nodeId) || empty($consoleId)) {
            \Log::error('Console: Paramètres manquants', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
            ]);
            return response()->json([
                'error' => 'Paramètres manquants: labId, nodeId et consoleId sont requis',
                'status' => 400,
            ], 400);
        }

        try {
            $log = $cisco->console->getConsoleLog($labId, $nodeId, $consoleId);

            if (isset($log['error'])) {
                \Log::warning('Console: Erreur lors de la récupération du log', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'console_id' => $consoleId,
                    'error' => $log['error'],
                    'status' => $log['status'] ?? null,
                    'is_timeout' => $log['is_timeout'] ?? false,
                ]);
                
                $statusCode = $log['status'] ?? 500;
                
                // Message d'erreur plus explicite pour les timeouts
                if (isset($log['is_timeout']) && $log['is_timeout']) {
                    $log['error'] = 'Le serveur CML ne répond pas dans les délais impartis. Le lab est peut-être en cours de démarrage ou surchargé.';
                }
                
                return response()->json($log, $statusCode);
            }

            \Log::info('Console: Log récupéré avec succès', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'has_log' => isset($log['log']),
                'log_type' => gettype($log['log'] ?? null),
            ]);

            return response()->json($log);
        } catch (\Exception $e) {
            \Log::error('Console: Exception lors de la récupération du log', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la récupération du log: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Endpoint ping pour vérifier la disponibilité de l'API console.
     * Cache: 5 secondes (appelé très fréquemment)
     */
    public function ping(): JsonResponse
    {
        $cacheKey = 'api:console:ping:' . md5(session('cml_token') ?? 'anonymous');
        
        $response = \Illuminate\Support\Facades\Cache::remember($cacheKey, 5, function() {
            $token = session('cml_token');
            
            return [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'has_token' => !empty($token),
            ];
        });
        
        return response()->json($response)->header('Cache-Control', 'public, max-age=5');
    }

    /**
     * Polling intelligent des logs console avec cache et parsing IOS
     */
    public function pollLogs(
        string $labId,
        string $nodeId,
        string $consoleId,
        CiscoApiService $cisco
    ): JsonResponse {
        // S'assurer que le token est disponible
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible',
                'status' => 401,
            ], 401);
        }

        try {
            // Utiliser le service de polling intelligent
            $pollingService = new \App\Services\Console\IntelligentPollingService($cisco);
            $result = $pollingService->getConsoleLogs($labId, $nodeId, $consoleId);

            if (isset($result['error'])) {
                \Log::warning('Console: Erreur lors du polling', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'console_id' => $consoleId,
                    'error' => $result['error'],
                    'status' => $result['status'] ?? null,
                    'is_timeout' => $result['is_timeout'] ?? false,
                ]);
                
                $statusCode = $result['rate_limited'] ?? false ? 429 : ($result['status'] ?? 500);
                return response()->json($result, $statusCode);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Console: Exception lors du polling', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Exception lors du polling: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Vider le cache des logs pour une console
     */
    public function clearLogsCache(
        string $labId,
        string $nodeId,
        string $consoleId
    ): JsonResponse {
        $pollingService = new \App\Services\Console\IntelligentPollingService(app(CiscoApiService::class));
        $pollingService->clearCache($labId, $nodeId, $consoleId);

        return response()->json([
            'success' => true,
            'message' => 'Cache vidé avec succès',
        ]);
    }
}


