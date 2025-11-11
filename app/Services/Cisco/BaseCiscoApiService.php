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
        $this->baseUrl = config('services.cml.base_url');
        $this->token = session('cml_token');
        $this->cache = app(CacheService::class);
        $this->resilience = app(ResilienceService::class);
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
        $response = Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->get("{$this->baseUrl}{$endpoint}");

        return $this->handleResponse($response, "Unable to fetch from {$endpoint}");
    }

    /**
     * Effectuer une requête POST
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->post("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleResponse($response, "Unable to post to {$endpoint}");
    }

    /**
     * Effectuer une requête PATCH
     */
    protected function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->patch("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleResponse($response, "Unable to patch {$endpoint}");
    }

    /**
     * Effectuer une requête PUT
     */
    protected function put(string $endpoint, array $data = [], array $headers = []): array
    {
        $response = Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->put("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleResponse($response, "Unable to put to {$endpoint}");
    }

    /**
     * Effectuer une requête DELETE
     */
    protected function delete(string $endpoint, array $headers = []): array
    {
        $response = Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->delete("{$this->baseUrl}{$endpoint}");

        return $this->handleResponse($response, "Unable to delete {$endpoint}");
    }

    /**
     * Gérer la réponse HTTP
     */
    protected function handleResponse(Response $response, string $errorMessage): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body()
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
}

