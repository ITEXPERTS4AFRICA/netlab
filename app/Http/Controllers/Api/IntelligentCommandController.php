<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IntelligentCommandGenerator;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntelligentCommandController extends Controller
{
    /**
     * Analyser le lab et générer des commandes intelligentes
     */
    public function analyzeLab(string $labId, IntelligentCommandGenerator $generator): JsonResponse
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $result = $generator->analyzeLabAndGenerateCommands($labId);

            return response()->json($result)->header('Cache-Control', 'public, max-age=300');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'analyse du lab', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de l\'analyse: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Générer un script de configuration automatique
     */
    public function generateScript(string $labId, IntelligentCommandGenerator $generator, Request $request): JsonResponse
    {
        $token = session('cml_token');
        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $options = $request->validate([
                'include_config' => 'boolean',
                'include_show' => 'boolean',
                'format' => 'string|in:script,json',
            ]);

            $result = $generator->generateConfigurationScript($labId, $options);

            return response()->json($result)->header('Cache-Control', 'public, max-age=300');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du script', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la génération: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Exécuter une commande générée automatiquement sur un node
     * Note: CML n'a pas d'API directe, donc on retourne la commande à exécuter
     */
    public function executeGeneratedCommand(
        string $labId,
        string $nodeId,
        Request $request,
        IntelligentCommandGenerator $generator
    ): JsonResponse {
        $token = session('cml_token');
        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        $validated = $request->validate([
            'command' => 'required|string',
            'category' => 'nullable|string',
        ]);

        // Call the Loadbalancer Command Gateway Service
        try {
            $gatewayUrl = config('services.loadbalancer.url', 'http://127.0.0.1:5000');

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post("{$gatewayUrl}/execute", [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'command' => $validated['command'],
                'category' => $validated['category'] ?? 'general',
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Command execution failed via gateway',
                'details' => $response->json(),
                'status' => $response->status(),
            ], $response->status());

        } catch (\Exception $e) {
            \Log::error('Gateway connection error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Could not connect to Command Gateway Service. Is the loadbalancer python server running?',
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Obtenir les commandes recommandées pour un node spécifique
     */
    public function getRecommendedCommands(
        string $labId,
        string $nodeId,
        IntelligentCommandGenerator $generator
    ): JsonResponse {
        $token = session('cml_token');
        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $analysis = $generator->analyzeLabAndGenerateCommands($labId);

            if (isset($analysis['error'])) {
                return response()->json($analysis, 500);
            }

            // Filtrer les commandes pour ce node spécifique
            $nodeCommands = $analysis['commands_by_node'][$nodeId] ?? null;

            if (!$nodeCommands) {
                return response()->json([
                    'error' => 'Aucune commande trouvée pour ce node',
                    'node_id' => $nodeId,
                ], 404);
            }

            return response()->json([
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'node_label' => $nodeCommands['node_label'],
                'node_definition' => $nodeCommands['node_definition'],
                'commands' => $nodeCommands['commands'],
                'total_commands' => count($nodeCommands['commands']),
            ])->header('Cache-Control', 'public, max-age=300');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes recommandées', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}


