<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LabDetailsController extends Controller
{
    /**
     * Obtenir tous les détails d'un lab (complet avec toutes les informations)
     */
    public function show(string $labId, CiscoApiService $cisco): JsonResponse
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

        $cacheKey = "api:lab:details:{$labId}";
        
        try {
            $details = Cache::remember($cacheKey, 30, function() use ($cisco, $labId) {
                // Récupérer toutes les informations du lab
                $lab = $cisco->labs->getLab($labId);
                $topology = $cisco->labs->getTopology($labId);
                $state = $cisco->labs->getLabState($labId);
                $events = $cisco->labs->getLabEvents($labId);
                $nodes = $cisco->nodes->getLabNodes($labId, true);
                $links = $cisco->links->getLabLinks($labId);
                $interfaces = $cisco->labs->getLabInterfaces($labId);
                $annotations = $cisco->labs->getLabAnnotations($labId);
                $simulationStats = $cisco->labs->getSimulationStats($labId);
                $layer3Addresses = $cisco->labs->getLabLayer3Addresses($labId);

                return [
                    'lab' => $lab,
                    'topology' => $topology,
                    'state' => $state,
                    'events' => $events,
                    'nodes' => $nodes,
                    'links' => $links,
                    'interfaces' => $interfaces,
                    'annotations' => $annotations,
                    'simulation_stats' => $simulationStats,
                    'layer3_addresses' => $layer3Addresses,
                ];
            });

            return response()->json($details)->header('Cache-Control', 'public, max-age=30');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des détails du lab', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de simulation d'un lab
     */
    public function simulationStats(string $labId, CiscoApiService $cisco): JsonResponse
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
            $stats = $cisco->labs->getSimulationStats($labId);
            
            if (isset($stats['error'])) {
                return response()->json($stats, $stats['status'] ?? 500);
            }

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Obtenir les adresses Layer 3 du lab
     */
    public function layer3Addresses(string $labId, CiscoApiService $cisco): JsonResponse
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
            $addresses = $cisco->labs->getLabLayer3Addresses($labId);
            
            if (isset($addresses['error'])) {
                return response()->json($addresses, $addresses['status'] ?? 500);
            }

            return response()->json($addresses);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}


