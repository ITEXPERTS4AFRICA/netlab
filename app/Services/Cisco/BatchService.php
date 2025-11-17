<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class BatchService extends BaseCiscoApiService
{
    /**
     * Démarrer plusieurs nodes en parallèle
     */
    public function startMultipleNodes(string $labId, array $nodeIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($nodeIds)->map(fn ($nodeId) =>
                $pool->as($nodeId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->put("{$this->baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/state/start")
            )->all()
        );

        $results = [];
        foreach ($responses as $nodeId => $response) {
            $results[$nodeId] = $response->successful()
                ? ['status' => 'started']
                : ['error' => $response->body()];
        }

        // Invalider le cache
        $this->cache->invalidateLab($labId);

        return $results;
    }

    /**
     * Arrêter plusieurs nodes en parallèle
     */
    public function stopMultipleNodes(string $labId, array $nodeIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($nodeIds)->map(fn ($nodeId) =>
                $pool->as($nodeId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->put("{$this->baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/state/stop")
            )->all()
        );

        $results = [];
        foreach ($responses as $nodeId => $response) {
            $results[$nodeId] = $response->successful()
                ? ['status' => 'stopped']
                : ['error' => $response->body()];
        }

        $this->cache->invalidateLab($labId);

        return $results;
    }

    /**
     * Créer plusieurs labs à partir de templates
     */
    public function createMultipleLabs(array $labsData): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($labsData)->map(fn ($data, $index) =>
                $pool->as($index)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->post("{$this->baseUrl}/api/v0/labs", $data)
            )->all()
        );

        $results = [];
        foreach ($responses as $index => $response) {
            $results[$index] = $response->successful()
                ? $response->json()
                : ['error' => $response->body()];
        }

        $this->cache->forget('labs:all');

        return $results;
    }

    /**
     * Démarrer plusieurs labs en parallèle
     */
    public function startMultipleLabs(array $labIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($labIds)->map(fn ($labId) =>
                $pool->as($labId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->put("{$this->baseUrl}/api/v0/labs/{$labId}/start")
            )->all()
        );

        $results = [];
        foreach ($responses as $labId => $response) {
            $results[$labId] = $response->successful()
                ? ['status' => 'started']
                : ['error' => $response->body()];

            if ($response->successful()) {
                $this->cache->invalidateLab($labId);
            }
        }

        return $results;
    }

    /**
     * Arrêter plusieurs labs en parallèle
     */
    public function stopMultipleLabs(array $labIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($labIds)->map(fn ($labId) =>
                $pool->as($labId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->put("{$this->baseUrl}/api/v0/labs/{$labId}/stop")
            )->all()
        );

        $results = [];
        foreach ($responses as $labId => $response) {
            $results[$labId] = $response->successful()
                ? ['status' => 'stopped']
                : ['error' => $response->body()];

            if ($response->successful()) {
                $this->cache->invalidateLab($labId);
            }
        }

        return $results;
    }

    /**
     * Mettre à jour plusieurs nodes en une fois
     */
    public function bulkUpdateNodes(string $labId, array $updates): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($updates)->map(fn ($data, $nodeId) =>
                $pool->as($nodeId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->patch("{$this->baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}", $data)
            )->all()
        );

        $results = [];
        foreach ($responses as $nodeId => $response) {
            $results[$nodeId] = $response->successful()
                ? $response->json()
                : ['error' => $response->body()];
        }

        $this->cache->invalidateLab($labId);

        return $results;
    }

    /**
     * Supprimer plusieurs labs en parallèle
     */
    public function deleteMultipleLabs(array $labIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($labIds)->map(fn ($labId) =>
                $pool->as($labId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->delete("{$this->baseUrl}/api/v0/labs/{$labId}")
            )->all()
        );

        $results = [];
        foreach ($responses as $labId => $response) {
            $results[$labId] = $response->successful()
                ? ['status' => 'deleted']
                : ['error' => $response->body()];

            if ($response->successful()) {
                $this->cache->invalidateLab($labId);
            }
        }

        $this->cache->forget('labs:all');

        return $results;
    }

    /**
     * Récupérer l'état de plusieurs nodes en parallèle
     */
    public function getMultipleNodeStates(string $labId, array $nodeIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($nodeIds)->map(fn ($nodeId) =>
                $pool->as($nodeId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->get("{$this->baseUrl}/api/v0/labs/{$labId}/nodes/{$nodeId}/state")
            )->all()
        );

        $results = [];
        foreach ($responses as $nodeId => $response) {
            $results[$nodeId] = $response->successful()
                ? $response->json()
                : ['error' => $response->body()];
        }

        return $results;
    }

    /**
     * Récupérer les informations de plusieurs labs en parallèle
     */
    public function getMultipleLabs(array $labIds): array
    {
        $responses = Http::pool(fn (Pool $pool) =>
            collect($labIds)->map(fn ($labId) =>
                $pool->as($labId)
                    ->withToken($this->token)
                    ->withOptions(['verify' => false])
                    ->get("{$this->baseUrl}/api/v0/labs/{$labId}")
            )->all()
        );

        $results = [];
        foreach ($responses as $labId => $response) {
            $results[$labId] = $response->successful()
                ? $response->json()
                : ['error' => $response->body()];
        }

        return $results;
    }
}

