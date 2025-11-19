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

        try {
        $consoles = $cisco->console->getNodeConsoles($labId, $nodeId);

            \Log::info('Console: Réponse getNodeConsoles', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'has_error' => isset($consoles['error']),
                'response_type' => gettype($consoles),
                'is_array' => is_array($consoles),
                'response_keys' => is_array($consoles) ? array_keys($consoles) : null,
            ]);

            if (isset($consoles['error'])) {
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
            ]);
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
            $consoleUrl = "{$baseUrl}/console/{$consoleKey}";
        }

        \Log::info('Console: Clé console obtenue avec succès', [
            'lab_id' => $payload['lab_id'],
            'node_id' => $payload['node_id'],
            'type' => $consoleType,
            'console_key' => $consoleKey,
            'console_url' => $consoleUrl,
        ]);

        // Retourner une réponse compatible avec l'ancien format de session
        return response()->json([
            'session_id' => $consoleKey,
            'id' => $consoleKey,
            'console_key' => $consoleKey,
            'console_url' => $consoleUrl,
            'url' => $consoleUrl,
            'lab_id' => $payload['lab_id'],
            'node_id' => $payload['node_id'],
            'type' => $consoleType,
            'protocol' => $consoleType === 'vnc' ? 'vnc' : 'console',
            // Note: CML n'utilise pas de WebSocket pour les consoles, donc pas de ws_href
            'ws_href' => null,
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

        $result = $cisco->console->closeConsoleSession($sessionId);

        if (isset($result['error'])) {
            \Log::warning('Console: Erreur lors de la fermeture de la session', [
                'session_id' => $sessionId,
                'error' => $result['error'],
            ]);
            return response()->json($result, $result['status'] ?? 500);
        }

        return response()->json($result);
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

        $log = $cisco->console->getConsoleLog($labId, $nodeId, $consoleId);

        if (isset($log['error'])) {
            \Log::warning('Console: Erreur lors de la récupération du log', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $log['error'],
            ]);
            return response()->json($log, $log['status'] ?? 500);
        }

        return response()->json($log);
    }

    /**
     * Endpoint ping pour vérifier la disponibilité de l'API console.
     */
    public function ping(): JsonResponse
    {
        $token = session('cml_token');
        
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'has_token' => !empty($token),
        ]);
    }
}


