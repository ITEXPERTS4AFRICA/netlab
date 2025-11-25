<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CinetPayHealthController extends Controller
{
    /**
     * Vérifier l'état de santé de CinetPay
     */
    public function check(): JsonResponse
    {
        try {
            $cinetPayService = new CinetPayService();
            $health = $cinetPayService->checkHealth();
            
            return response()->json($health);
        } catch (\Exception $e) {
            Log::error('CinetPay health check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}

