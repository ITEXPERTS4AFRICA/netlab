<?php

namespace App\Traits;

use App\Helpers\CmlConfigHelper;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Log;

/**
 * Trait pour gérer automatiquement le rafraîchissement du token CML
 * Utilise les credentials par défaut pour rafraîchir le token silencieusement
 */
trait HandlesCmlToken
{
    /**
     * Obtenir ou rafraîchir le token CML automatiquement
     * 
     * @param CiscoApiService|null $cmlService Service CML optionnel (sera créé si non fourni)
     * @return string|null Token CML ou null si impossible de s'authentifier
     */
    protected function getOrRefreshCmlToken(?CiscoApiService $cmlService = null): ?string
    {
        // Vérifier d'abord si un token existe dans la session
        $token = session('cml_token');
        
        if ($token) {
            return $token;
        }

        // Pas de token, essayer de rafraîchir automatiquement
        return $this->refreshCmlTokenSilently($cmlService);
    }

    /**
     * Rafraîchir le token CML silencieusement avec les credentials par défaut
     * 
     * @param CiscoApiService|null $cmlService Service CML optionnel
     * @return string|null Token CML ou null si échec
     */
    protected function refreshCmlTokenSilently(?CiscoApiService $cmlService = null): ?string
    {
        try {
            // Vérifier que la configuration est complète
            if (!CmlConfigHelper::isConfigured()) {
                Log::warning('Configuration CML incomplète, impossible de rafraîchir le token');
                return null;
            }

            // Obtenir les credentials par défaut
            $username = CmlConfigHelper::getDefaultUsername();
            $password = CmlConfigHelper::getDefaultPassword();
            $baseUrl = CmlConfigHelper::getBaseUrl();

            if (!$baseUrl || !$username || !$password) {
                Log::warning('Credentials CML par défaut incomplets', [
                    'has_base_url' => !empty($baseUrl),
                    'has_username' => !empty($username),
                    'has_password' => !empty($password),
                ]);
                return null;
            }

            // Créer le service si non fourni
            if (!$cmlService) {
                $cmlService = app(CiscoApiService::class);
            }

            // S'assurer que le service utilise la bonne URL
            $cmlService->setBaseUrl($baseUrl);

            // Rafraîchir le token
            if ($cmlService->refreshToken()) {
                $newToken = session('cml_token');
                Log::info('Token CML rafraîchi automatiquement avec succès');
                return $newToken;
            }

            Log::warning('Échec du rafraîchissement automatique du token CML');
            return null;
        } catch (\Exception $e) {
            Log::error('Exception lors du rafraîchissement automatique du token CML', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Vérifier et rafraîchir le token CML si nécessaire
     * Retourne true si un token valide est disponible, false sinon
     * 
     * @param CiscoApiService|null $cmlService Service CML optionnel
     * @return bool
     */
    protected function ensureCmlToken(?CiscoApiService $cmlService = null): bool
    {
        $token = $this->getOrRefreshCmlToken($cmlService);
        return $token !== null;
    }
}

