<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabConfigController extends Controller
{
    /**
     * Obtenir la configuration complète du lab (topologie YAML)
     */
    public function getLabConfig(string $labId, CiscoApiService $cisco): JsonResponse
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
            \Log::info('Récupération de la configuration complète du lab', [
                'lab_id' => $labId,
            ]);

            // Récupérer la topologie complète
            $topology = $cisco->labs->getTopology($labId);
            
            if (isset($topology['error'])) {
                return response()->json($topology, $topology['status'] ?? 500);
            }

            // Récupérer aussi le lab pour les métadonnées
            $lab = $cisco->labs->getLab($labId);
            
            if (isset($lab['error'])) {
                return response()->json($lab, $lab['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'lab_id' => $labId,
                'lab' => $lab,
                'topology' => $topology,
                'yaml' => $this->convertTopologyToYaml($topology, $lab),
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la récupération de la config du lab', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Mettre à jour la configuration complète du lab
     */
    public function updateLabConfig(
        string $labId,
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
            'topology' => 'required|array',
            'yaml' => 'nullable|string',
        ]);

        try {
            \Log::info('Mise à jour de la configuration du lab', [
                'lab_id' => $labId,
                'has_topology' => $request->has('topology'),
                'has_yaml' => $request->has('yaml'),
            ]);

            // Si YAML fourni, l'importer
            if ($request->has('yaml') && !empty($request->input('yaml'))) {
                $result = $cisco->import->importLabFromYaml($request->input('yaml'), [
                    'update_if_exists' => true,
                    'lab_id' => $labId,
                ]);
            } else {
                // Sinon, mettre à jour la topologie directement
                $result = $cisco->labs->updateLab($labId, [
                    'topology' => $request->input('topology'),
                ]);
            }

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de la mise à jour de la config', [
                    'lab_id' => $labId,
                    'error' => $result['error'],
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            // Invalider le cache
            \Cache::forget("api:lab:details:{$labId}");
            \Cache::forget("api:labs:topology:{$labId}");

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la mise à jour de la config', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Convertir la topologie en YAML (format simplifié)
     */
    private function convertTopologyToYaml(array $topology, array $lab): string
    {
        $yaml = "lab:\n";
        $yaml .= "  title: " . ($lab['title'] ?? 'Untitled Lab') . "\n";
        $yaml .= "  description: " . ($lab['description'] ?? '') . "\n";
        $yaml .= "  notes: " . ($lab['notes'] ?? '') . "\n";
        $yaml .= "\n";
        $yaml .= "nodes:\n";
        
        if (isset($topology['nodes']) && is_array($topology['nodes'])) {
            foreach ($topology['nodes'] as $node) {
                $yaml .= "  - id: " . ($node['id'] ?? '') . "\n";
                $yaml .= "    label: " . ($node['label'] ?? '') . "\n";
                $yaml .= "    node_definition: " . ($node['node_definition'] ?? '') . "\n";
                if (isset($node['x']) && isset($node['y'])) {
                    $yaml .= "    x: " . $node['x'] . "\n";
                    $yaml .= "    y: " . $node['y'] . "\n";
                }
                if (isset($node['configuration'])) {
                    $yaml .= "    configuration: |\n";
                    $config = is_array($node['configuration']) 
                        ? ($node['configuration'][0]['content'] ?? '')
                        : $node['configuration'];
                    foreach (explode("\n", $config) as $line) {
                        $yaml .= "      " . $line . "\n";
                    }
                }
            }
        }
        
        $yaml .= "\nlinks:\n";
        if (isset($topology['links']) && is_array($topology['links'])) {
            foreach ($topology['links'] as $link) {
                $yaml .= "  - id: " . ($link['id'] ?? '') . "\n";
                $yaml .= "    n1: " . ($link['n1'] ?? $link['node_a'] ?? '') . "\n";
                $yaml .= "    n2: " . ($link['n2'] ?? $link['node_b'] ?? '') . "\n";
                $yaml .= "    i1: " . ($link['i1'] ?? $link['interface_a'] ?? '') . "\n";
                $yaml .= "    i2: " . ($link['i2'] ?? $link['interface_b'] ?? '') . "\n";
            }
        }

        return $yaml;
    }
}


