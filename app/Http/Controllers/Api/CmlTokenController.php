<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use App\Traits\HandlesCmlToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CmlTokenController extends Controller
{
    use HandlesCmlToken;

    /**
     * Rafraîchir le token CML silencieusement
     * Endpoint pour le frontend pour rafraîchir le token sans afficher d'erreur
     */
    public function refresh(Request $request, CiscoApiService $cmlService): JsonResponse
    {
        try {
            $token = $this->refreshCmlTokenSilently($cmlService);

            if ($token) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token CML rafraîchi avec succès',
                    'has_token' => true,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir le token CML. Vérifiez la configuration.',
                'has_token' => false,
            ], 200); // 200 pour ne pas déclencher d'erreur côté frontend
        } catch (\Exception $e) {
            Log::error('Erreur lors du rafraîchissement du token CML via API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token: ' . $e->getMessage(),
                'has_token' => false,
            ], 200); // 200 pour ne pas déclencher d'erreur côté frontend
        }
    }

    /**
     * Vérifier si un token CML est disponible
     */
    public function check(Request $request): JsonResponse
    {
        $token = session('cml_token');

        return response()->json([
            'has_token' => !empty($token),
            'token_exists' => !empty($token),
        ]);
    }
}

