<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Extraire la configuration d'un node
     */
    public function extractNodeConfig(string $labId, string $nodeId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            \Log::info('Extraction de la configuration du node', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
            ]);

            $result = $cisco->nodes->extractNodeConfiguration($labId, $nodeId);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de l\'extraction de la configuration', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'error' => $result['error'],
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration extraite avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'extraction de la configuration', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de l\'extraction: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Uploader une configuration pour un node
     */
    public function uploadNodeConfig(
        string $labId,
        string $nodeId,
        Request $request,
        CiscoApiService $cisco
    ): JsonResponse {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        $request->validate([
            'configuration' => 'required|string|max:20971520', // 20MB max
            'name' => 'nullable|string|max:64',
        ]);

        try {
            \Log::info('Upload de configuration pour le node', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'config_size' => strlen($request->input('configuration')),
            ]);

            // Récupérer le node actuel
            $node = $cisco->nodes->getNode($labId, $nodeId);
            
            if (isset($node['error'])) {
                return response()->json($node, $node['status'] ?? 500);
            }

            // Préparer la configuration
            $configData = [
                'configuration' => $request->input('configuration'),
            ];

            // Si un nom de fichier est fourni, utiliser le format NodeConfigurationFile
            if ($request->has('name')) {
                $configData['configuration'] = [
                    [
                        'name' => $request->input('name'),
                        'content' => $request->input('configuration'),
                    ],
                ];
            }

            // Mettre à jour le node avec la nouvelle configuration
            $result = $cisco->nodes->updateNode($labId, $nodeId, $configData);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de l\'upload de la configuration', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'error' => $result['error'],
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration uploadée avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'upload de la configuration', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Obtenir la configuration d'un node
     */
    public function getNodeConfig(string $labId, string $nodeId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $node = $cisco->nodes->getNode($labId, $nodeId);

            if (isset($node['error'])) {
                return response()->json($node, $node['status'] ?? 500);
            }

            $configuration = $node['configuration'] ?? null;

            return response()->json([
                'success' => true,
                'configuration' => $configuration,
                'node_id' => $nodeId,
                'lab_id' => $labId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la récupération de la configuration', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Exporter la configuration d'un node (téléchargement)
     */
    public function exportNodeConfig(string $labId, string $nodeId, CiscoApiService $cisco)
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible.',
                'status' => 401,
            ], 401);
        }

        try {
            $node = $cisco->nodes->getNode($labId, $nodeId);

            if (isset($node['error'])) {
                return response()->json($node, $node['status'] ?? 500);
            }

            $configuration = $node['configuration'] ?? '';
            $nodeLabel = $node['label'] ?? $nodeId;

            // Si la configuration est un tableau de fichiers, extraire le contenu
            if (is_array($configuration)) {
                $configContent = '';
                foreach ($configuration as $file) {
                    if (isset($file['name']) && isset($file['content'])) {
                        $configContent .= "! File: {$file['name']}\n";
                        $configContent .= $file['content'] . "\n\n";
                    }
                }
                $configuration = $configContent;
            }

            $filename = "{$nodeLabel}_config.txt";

            return response($configuration, 200)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'export de la configuration', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de l\'export: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Exporter le lab complet (YAML)
     */
    public function exportLab(string $labId, CiscoApiService $cisco)
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible.',
                'status' => 401,
            ], 401);
        }

        try {
            $result = $cisco->labs->downloadLab($labId);

            if (isset($result['error'])) {
                return response()->json($result, $result['status'] ?? 500);
            }

            $lab = $cisco->labs->getLab($labId);
            $labTitle = $lab['title'] ?? $labId;
            $filename = "{$labTitle}_export.yaml";

            return response($result, 200)
                ->header('Content-Type', 'application/yaml')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'export du lab', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de l\'export: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}


