<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Http;

class AuthService extends BaseCiscoApiService
{
    /**
     * Authentification étendue avec nom d'utilisateur et mot de passe
     */
    public function authExtended(string $username, string $password): array
    {
        try {
            if (empty($this->baseUrl)) {
                return ['error' => 'URL de base CML non configurée. Veuillez configurer l\'URL dans les paramètres.'];
            }

            $url = "{$this->baseUrl}/api/v0/auth_extended";
            
            \Log::info('Tentative d\'authentification CML', [
                'url' => $url,
                'username' => $username,
                'base_url' => $this->baseUrl,
            ]);

            $response = Http::withOptions(['verify' => false, 'timeout' => 15])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'username' => $username,
                    'password' => $password,
                ]);

            $status = $response->status();
            $body = $response->body();
            $jsonData = $response->json();

            \Log::info('Réponse authentification CML', [
                'status' => $status,
                'has_token' => isset($jsonData['token']),
                'response_preview' => substr($body, 0, 200),
            ]);

            if ($response->successful()) {
                $data = $jsonData ?: [];
                if (isset($data['token'])) {
                    session()->put('cml_token', $data['token']);
                    return $data;
                }
                
                // Si pas de token mais succès, retourner quand même les données
                return $data;
            }

            // Gestion des différents codes d'erreur HTTP
            $errorMessage = 'Authentification échouée';
            $errorDetails = [];

            switch ($status) {
                case 401:
                    $errorMessage = 'Non autorisé. Vérifiez vos identifiants.';
                    break;
                case 403:
                    $errorMessage = 'Accès refusé. Identifiants invalides ou permissions insuffisantes.';
                    break;
                case 404:
                    $errorMessage = 'Endpoint non trouvé. Vérifiez que l\'URL de base CML est correcte.';
                    $errorDetails['url'] = $url;
                    break;
                case 500:
                case 502:
                case 503:
                    $errorMessage = 'Erreur serveur CML. Le serveur est peut-être indisponible.';
                    break;
                default:
                    $errorMessage = "Erreur d'authentification (code HTTP: {$status})";
            }

            // Ajouter les détails de la réponse si disponibles
            if ($jsonData && is_array($jsonData)) {
                $errorDetails = array_merge($errorDetails, $jsonData);
            } else {
                $errorDetails['raw_response'] = substr($body, 0, 500);
            }

            \Log::warning('Échec authentification CML', [
                'status' => $status,
                'username' => $username,
                'url' => $url,
                'error_details' => $errorDetails,
            ]);

            return [
                'error' => $errorMessage,
                'status' => $status,
                'details' => $errorDetails,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            $isTimeout = stripos($errorMessage, 'timeout') !== false || 
                        stripos($errorMessage, 'timed out') !== false ||
                        stripos($errorMessage, 'after') !== false;
            
            \Log::warning('Erreur de connexion CML lors de l\'authentification', [
                'error' => $errorMessage,
                'url' => $this->baseUrl ?? 'non définie',
                'username' => $username,
                'is_timeout' => $isTimeout,
            ]);
            
            // Message d'erreur plus détaillé selon le type d'erreur
            $userMessage = 'Erreur de connexion au serveur CML.';
            if ($isTimeout) {
                $userMessage = 'Timeout de connexion au serveur CML. Le serveur ne répond pas dans les délais impartis (15 secondes).';
            } elseif (stripos($errorMessage, 'Could not connect') !== false || 
                      stripos($errorMessage, 'Failed to connect') !== false) {
                $userMessage = 'Impossible de se connecter au serveur CML. Le serveur est peut-être indisponible ou l\'URL est incorrecte.';
            }
            
            return [
                'error' => $userMessage,
                'status' => 503,
                'connection_error' => true,
                'is_timeout' => $isTimeout,
                'details' => [
                    'message' => $errorMessage,
                    'base_url' => $this->baseUrl,
                    'url_used' => isset($url) ? $url : ($this->baseUrl ? "{$this->baseUrl}/api/v0/auth_extended" : 'non définie'),
                    'suggestions' => [
                        'Vérifiez que le serveur CML est démarré et accessible',
                        'Vérifiez que l\'URL de base est correcte (format: https://ip-ou-domaine)',
                        'Vérifiez votre connexion réseau et les règles de pare-feu',
                        'Vérifiez que le port 443 (HTTPS) est ouvert',
                        'Essayez d\'accéder à l\'URL dans un navigateur pour vérifier l\'accessibilité',
                    ],
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'authentification CML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $this->baseUrl ?? 'non définie',
                'username' => $username,
            ]);
            return [
                'error' => 'Exception lors de l\'authentification: ' . $e->getMessage(),
                'status' => 500,
                'details' => app()->environment('local') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ];
        }
    }

    /**
     * Déconnexion
     */
    public function logout($token = null): mixed
    {
        $tokenToUse = $token ?? $this->getToken();

        $response = Http::withToken($tokenToUse)
            ->withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/json'])
            ->delete("{$this->baseUrl}/api/v0/logout");

        return $response->successful() ? $response : $response->json();
    }

    /**
     * Révoquer le token actuel
     */
    public function revokeToken(): void
    {
        $token = $this->getToken();
        if (!$token) {
            return;
        }

        try {
            $url = "{$this->baseUrl}/api/v0/revoke";
            Http::withToken($token)
                ->withOptions(['verify' => false])
                ->post($url);
        } catch (\Exception $e) {
            // ignore - revoke best effort
        }

        // Nettoyer la session
        session()->forget('cml_token');
    }

    /**
     * Obtenir le timeout de session web
     */
    public function getWebSessionTimeout(): array
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$this->baseUrl}/api/v0/web_session_timeout");

        return $this->handleResponse($response, 'Unable to get web session timeout');
    }

    /**
     * Mettre à jour le timeout de session web
     */
    public function updateWebSessionTimeout(int $timeout): array
    {
        return $this->patch("/api/v0/web_session_timeout/{$timeout}");
    }

    /**
     * Authentification simple (retourne uniquement le token)
     */
    public function authenticate(string $username, string $password): array
    {
        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/v0/authenticate", [
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data)) {
                    session()->put('cml_token', $data);
                }
                return ['token' => $data];
            }

            return ['error' => 'Authentification incorrecte', 'status' => $response->status()];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML lors de l\'authentification', [
                'error' => $e->getMessage(),
            ]);
            return ['error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.', 'status' => 503, 'connection_error' => true];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'authentification CML', [
                'error' => $e->getMessage(),
            ]);
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Vérifier si l'appel API est correctement authentifié
     */
    public function authOk(): array
    {
        return $this->get('/api/v0/authok');
    }
}

