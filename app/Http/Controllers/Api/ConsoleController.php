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
            ]);
            return response()->json($consoles, $consoles['status'] ?? 500);
        }
        
        // Normaliser la réponse : l'API peut retourner un tableau directement ou un objet avec une clé
        if (!is_array($consoles)) {
            \Log::warning('Console: Réponse invalide (pas un tableau)', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'response_type' => gettype($consoles),
            ]);
            $consoles = [];
        } elseif (isset($consoles['consoles']) && is_array($consoles['consoles'])) {
            // Si la réponse est un objet avec une clé 'consoles', extraire le tableau
            $consoles = $consoles['consoles'];
        } elseif (!isset($consoles[0]) && !empty($consoles)) {
            // Si c'est un objet unique, le mettre dans un tableau
            $consoles = [$consoles];
        }

        $types = $cisco->console->getAvailableConsoleTypes($labId, $nodeId);

        if (isset($types['error'])) {
            \Log::warning('Console: Erreur lors de la récupération des types', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $types['error'],
            ]);
            // Ne pas échouer si les types ne peuvent pas être récupérés
            // Utiliser les types par défaut
            $types = [
                'serial' => false,
                'vnc' => false,
                'console' => true, // Toujours disponible
            ];
        }

        \Log::info('Console: Réponse finale', [
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'consoles_count' => count($consoles),
            'available_types' => $types,
        ]);

        return response()->json([
            'consoles' => $consoles,
            'available_types' => $types,
        ]);
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

        // Extraire la clé console (peut être une string ou un array avec 'id')
        $consoleKey = is_string($keyResponse) ? $keyResponse : ($keyResponse['id'] ?? $keyResponse['key'] ?? null);
        
        if (!$consoleKey) {
            \Log::error('Console: Clé console introuvable dans la réponse', [
                'lab_id' => $payload['lab_id'],
                'node_id' => $payload['node_id'],
                'type' => $consoleType,
                'response' => $keyResponse,
            ]);
            
            return response()->json([
                'error' => 'Clé console introuvable. Le node peut ne pas avoir de console disponible.',
                'status' => 404,
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
}


