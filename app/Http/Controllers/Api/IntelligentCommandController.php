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

        // CML n'a pas d'API pour exécuter des commandes directement
        // On retourne la commande à exécuter et les instructions
        return response()->json([
            'lab_id' => $labId,
            'node_id' => $nodeId,
            'command' => $validated['command'],
            'category' => $validated['category'] ?? 'general',
            'instructions' => [
                'step_1' => 'La commande doit être tapée dans la console IOS',
                'step_2' => 'Utiliser le polling des logs pour récupérer les résultats',
                'step_3' => 'GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log',
            ],
            'note' => 'CML n\'expose pas d\'API REST pour exécuter des commandes CLI. La commande doit être tapée manuellement dans la console.',
        ]);
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


