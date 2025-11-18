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
            $url = "{$this->baseUrl}/api/v0/auth_extended";
            $response = Http::withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['token'])) {
                    session()->put('cml_token', $data['token']);
                }
                return $data;
            }

            if ($response->status() === 403) {
                return ['error' => 'Accès refusé. Identifiants invalides.'];
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

