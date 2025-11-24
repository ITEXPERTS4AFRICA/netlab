<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LabController extends Controller
{
    /**
     * Récupérer tous les labs disponibles sur CML.
     * Cache: 30 secondes pour éviter les appels répétés
     */
    public function index(CiscoApiService $cisco): JsonResponse
    {
        $cacheKey = 'api:labs:index:' . md5(session('cml_token') ?? 'anonymous');
        
        $labs = Cache::remember($cacheKey, 30, function() use ($cisco) {
            return $cisco->labs->getLabs();
        });

        if (isset($labs['error'])) {
            // En cas d'erreur, invalider le cache
            Cache::forget($cacheKey);
            return response()->json($labs, $labs['status'] ?? 500);
        }

        return response()->json($labs)->header('Cache-Control', 'public, max-age=30');
    }

    /**
     * Obtenir la topologie d'un lab.
     * Cache: 60 secondes (topologie change rarement)
     */
    public function topology(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $cacheKey = 'api:labs:topology:' . $lab->cml_id;
        
        $topology = Cache::remember($cacheKey, 60, function() use ($cisco, $lab) {
            return $cisco->labs->getTopology($lab->cml_id);
        });

        if (isset($topology['error'])) {
            Cache::forget($cacheKey);
            return response()->json($topology, $topology['status'] ?? 500);
        }

        return response()->json($topology)->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * Obtenir l'état courant d'un lab.
     * Cache: 10 secondes (état change fréquemment)
     */
    public function state(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $cacheKey = 'api:labs:state:' . $lab->cml_id;
        
        $state = Cache::remember($cacheKey, 10, function() use ($cisco, $lab) {
            return $cisco->labs->getLabState($lab->cml_id);
        });

        if (isset($state['error'])) {
            Cache::forget($cacheKey);
            return response()->json($state, $state['status'] ?? 500);
        }

        return response()->json($state)->header('Cache-Control', 'public, max-age=10');
    }

    /**
     * Vérifier la convergence d'un lab.
     * Cache: 15 secondes (convergence change modérément)
     */
    public function convergence(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $cacheKey = 'api:labs:convergence:' . $lab->cml_id;
        
        $status = Cache::remember($cacheKey, 15, function() use ($cisco, $lab) {
            return $cisco->labs->checkIfConverged($lab->cml_id);
        });

        if (isset($status['error'])) {
            Cache::forget($cacheKey);
            return response()->json($status, $status['status'] ?? 500);
        }

        return response()->json($status)->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Obtenir les détails d'un lab précis.
     * Cache: 30 secondes
     */
    public function show(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $cacheKey = 'api:labs:show:' . $lab->cml_id;
        
        $detail = Cache::remember($cacheKey, 30, function() use ($cisco, $lab) {
            return $cisco->labs->getLab($lab->cml_id);
        });

        if (isset($detail['error'])) {
            Cache::forget($cacheKey);
            return response()->json($detail, $detail['status'] ?? 500);
        }

        return response()->json($detail)->header('Cache-Control', 'public, max-age=30');
    }
}


