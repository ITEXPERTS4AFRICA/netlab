<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LabEventsController extends Controller
{
    /**
     * Obtenir les événements d'un lab
     */
    public function index(string $labId, Request $request, CiscoApiService $cisco): JsonResponse
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

        $cacheKey = "api:lab:events:{$labId}";
        $cacheTtl = 10; // Cache court car les events changent fréquemment

        try {
            $events = Cache::remember($cacheKey, $cacheTtl, function() use ($cisco, $labId) {
                return $cisco->labs->getLabEvents($labId);
            });

            if (isset($events['error'])) {
                Cache::forget($cacheKey);
                return response()->json($events, $events['status'] ?? 500);
            }

            // Filtrer par type si demandé
            $type = $request->query('type');
            if ($type && is_array($events)) {
                $events = array_filter($events, function($event) use ($type) {
                    return isset($event['type']) && $event['type'] === $type;
                });
                $events = array_values($events);
            }

            // Limiter le nombre de résultats si demandé
            $limit = $request->query('limit');
            if ($limit && is_numeric($limit) && is_array($events)) {
                $events = array_slice($events, 0, (int)$limit);
            }
            
            // Support du paramètre 'after' pour filtrer les événements après un ID donné
            $after = $request->query('after');
            if ($after && is_array($events)) {
                $foundAfter = false;
                $filteredEvents = [];
                foreach ($events as $event) {
                    if ($foundAfter) {
                        $filteredEvents[] = $event;
                    } elseif (isset($event['id']) && $event['id'] === $after) {
                        $foundAfter = true;
                    }
                }
                $events = $filteredEvents;
            }

            return response()->json([
                'events' => $events,
                'count' => is_array($events) ? count($events) : 0,
            ])->header('Cache-Control', 'public, max-age=10');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des événements', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération des événements: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Obtenir les événements d'un node spécifique
     */
    public function nodeEvents(string $labId, string $nodeId, Request $request, CiscoApiService $cisco): JsonResponse
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
            $allEvents = $cisco->labs->getLabEvents($labId);

            if (isset($allEvents['error'])) {
                return response()->json($allEvents, $allEvents['status'] ?? 500);
            }

            // Filtrer les événements pour ce node spécifique
            $nodeEvents = [];
            if (is_array($allEvents)) {
                foreach ($allEvents as $event) {
                    if (isset($event['node_id']) && $event['node_id'] === $nodeId) {
                        $nodeEvents[] = $event;
                    }
                }
            }

            // Limiter le nombre de résultats si demandé
            $limit = $request->query('limit');
            if ($limit && is_numeric($limit)) {
                $nodeEvents = array_slice($nodeEvents, 0, (int)$limit);
            }

            return response()->json([
                'events' => $nodeEvents,
                'count' => count($nodeEvents),
                'node_id' => $nodeId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des événements du node', [
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

    /**
     * Obtenir les événements d'une interface spécifique
     */
    public function interfaceEvents(string $labId, string $interfaceId, Request $request, CiscoApiService $cisco): JsonResponse
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
            $allEvents = $cisco->labs->getLabEvents($labId);

            if (isset($allEvents['error'])) {
                return response()->json($allEvents, $allEvents['status'] ?? 500);
            }

            // Filtrer les événements pour cette interface spécifique
            $interfaceEvents = [];
            if (is_array($allEvents)) {
                foreach ($allEvents as $event) {
                    if (isset($event['interface_id']) && $event['interface_id'] === $interfaceId) {
                        $interfaceEvents[] = $event;
                    }
                }
            }

            // Limiter le nombre de résultats si demandé
            $limit = $request->query('limit');
            if ($limit && is_numeric($limit)) {
                $interfaceEvents = array_slice($interfaceEvents, 0, (int)$limit);
            }

            return response()->json([
                'events' => $interfaceEvents,
                'count' => count($interfaceEvents),
                'interface_id' => $interfaceId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des événements de l\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}

