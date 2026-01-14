<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class LabController extends Controller
{
    /**
     * Récupérer tous les labs disponibles sur CML.
     */
    public function index(CiscoApiService $cisco): JsonResponse
    {

        dd($cisco->labs->getLabs());
        $labs = $cisco->labs->getLabs();

        if (isset($labs['error'])) {
            return response()->json($labs, $labs['status'] ?? 500);
        }

        return response()->json($labs);
    }

    /**
     * Obtenir la topologie d'un lab.
     */
    public function topology(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $topology = $cisco->labs->getTopology($lab->cml_id);

        if (isset($topology['error'])) {
            return response()->json($topology, $topology['status'] ?? 500);
        }

        return response()->json($topology);
    }

    /**
     * Obtenir l'état courant d'un lab.
     */
    public function state(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $state = $cisco->labs->getLabState($lab->cml_id);

        if (isset($state['error'])) {
            return response()->json($state, $state['status'] ?? 500);
        }

        return response()->json($state);
    }

    /**
     * Vérifier la convergence d'un lab.
     */
    public function convergence(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $status = $cisco->labs->checkIfConverged($lab->cml_id);

        if (isset($status['error'])) {
            return response()->json($status, $status['status'] ?? 500);
        }

        return response()->json($status);
    }

    /**
     * Obtenir les détails d'un lab précis.
     */
    public function show(Lab $lab, CiscoApiService $cisco): JsonResponse
    {
        $detail = $cisco->labs->getLab($lab->cml_id);

        if (isset($detail['error'])) {
            return response()->json($detail, $detail['status'] ?? 500);
        }

        return response()->json($detail);
    }
}


