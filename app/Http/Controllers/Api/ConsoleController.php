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
        $consoles = $cisco->console->getNodeConsoles($labId, $nodeId);

        if (isset($consoles['error'])) {
            return response()->json($consoles, $consoles['status'] ?? 500);
        }

        $types = $cisco->console->getAvailableConsoleTypes($labId, $nodeId);

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
        $payload = $request->validate([
            'lab_id' => ['required', 'string'],
            'node_id' => ['required', 'string'],
            'type' => ['nullable', 'string'],
            'protocol' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
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

        $session = $cisco->console->createConsoleSession(
            $payload['lab_id'],
            $payload['node_id'],
            $options
        );

        if (isset($session['error'])) {
            return response()->json($session, $session['status'] ?? 500);
        }

        return response()->json($session);
    }

    /**
     * Récupérer les sessions console actives.
     */
    public function sessions(CiscoApiService $cisco): JsonResponse
    {
        $sessions = $cisco->console->getConsoleSessions();

        if (isset($sessions['error'])) {
            return response()->json($sessions, $sessions['status'] ?? 500);
        }

        return response()->json($sessions);
    }

    /**
     * Fermer une session console.
     */
    public function destroy(string $sessionId, CiscoApiService $cisco): JsonResponse
    {
        $result = $cisco->console->closeConsoleSession($sessionId);

        if (isset($result['error'])) {
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
        $log = $cisco->console->getConsoleLog($labId, $nodeId, $consoleId);

        if (isset($log['error'])) {
            return response()->json($log, $log['status'] ?? 500);
        }

        return response()->json($log);
    }
}


