<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

abstract class BaseCiscoApiService
{
    protected $baseUrl;
    protected $token;
    protected $cache;
    protected $resilience;
    protected $enableCache = true;
    protected $enableResilience = true;

    public function __construct()
    {
        // Utiliser la configuration depuis la base de données (Setting) en priorité
        $this->baseUrl = $this->getCmlBaseUrl();
        $this->token = session('cml_token');
        $this->cache = app(CacheService::class);
        $this->resilience = app(ResilienceService::class);
    }

    /**
     * Obtenir l'URL de base CML depuis la configuration
     * Priorité : Base de données (Setting) > config > .env
     */
    protected function getCmlBaseUrl(): ?string
    {
        return \App\Helpers\CmlConfigHelper::getBaseUrl();
    }

    /**
     * Obtenir les credentials CML depuis la configuration
     * Priorité : Base de données (Setting) > config > .env
     */
    protected function getCmlCredentials(): array
    {
        return \App\Helpers\CmlConfigHelper::getCredentials();
    }

    /**
     * Activer/désactiver le cache pour ce service
     */
    public function enableCache(bool $enable = true): self
    {
        $this->enableCache = $enable;
        return $this;
    }

    /**
     * GET avec cache automatique
     */
    protected function getCached(string $endpoint, string $cacheKey, ?int $ttl = null, array $headers = []): array
    {
        if (!$this->enableCache) {
            return $this->get($endpoint, $headers);
        }

        return $this->cache->remember(
            $cacheKey,
            $ttl ?? 300,
            fn() => $this->get($endpoint, $headers)
        );
    }

    /**
     * Effectuer une requête GET (avec resilience)
     */
    protected function get(string $endpoint, array $headers = []): array
    {
        if (!$this->enableResilience) {
            return $this->executeGet($endpoint, $headers);
        }

        return $this->resilience->withResilience(
            'cml-api',
            fn() => $this->executeGet($endpoint, $headers)
        );
    }

    /**
     * Exécuter GET sans resilience
     */
    private function executeGet(string $endpoint, array $headers = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->get("{$this->baseUrl}{$endpoint}");

            return $this->handleResponse($response, "Unable to fetch from {$endpoint}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Effectuer une requête POST
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->post("{$this->baseUrl}{$endpoint}", $data);

            return $this->handleResponse($response, "Unable to post to {$endpoint}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Effectuer une requête PATCH
     */
    protected function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->patch("{$this->baseUrl}{$endpoint}", $data);

            return $this->handleResponse($response, "Unable to patch {$endpoint}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Effectuer une requête PUT
     */
    protected function put(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->put("{$this->baseUrl}{$endpoint}", $data);

            return $this->handleResponse($response, "Unable to put to {$endpoint}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Effectuer une requête DELETE
     */
    protected function delete(string $endpoint, array $headers = []): array
    {
        try {
            $response = Http::withToken($this->token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->delete("{$this->baseUrl}{$endpoint}");

            return $this->handleResponse($response, "Unable to delete {$endpoint}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::warning('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Gérer la réponse HTTP
     */
    protected function handleResponse(Response $response, string $errorMessage): array
    {
        if ($response->successful()) {
            $json = $response->json();
            // S'assurer qu'on retourne toujours un array
            return is_array($json) ? $json : ['data' => $json];
        }

        // Logger les détails de l'erreur pour le débogage
        \Log::warning('Erreur API CML', [
            'endpoint' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);

        // Essayer de parser le body comme JSON pour obtenir plus de détails
        $errorBody = $response->json();
        $errorDetail = is_array($errorBody) && isset($errorBody['description']) 
            ? $errorBody['description'] 
            : $response->body();

        return [
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $errorDetail,
            'detail' => $errorBody,
        ];
    }

    /**
     * Gérer la réponse pour un body brut (YAML, fichiers, etc.)
     */
    protected function handleRawResponse(Response $response, string $errorMessage)
    {
        if ($response->successful()) {
            return $response->body();
        }

        return [
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body()
        ];
    }

    /**
     * Définir le token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
        session()->put('cml_token', $token);
    }

    /**
     * Obtenir le token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Définir l'URL de base
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Obtenir l'URL de base
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}

