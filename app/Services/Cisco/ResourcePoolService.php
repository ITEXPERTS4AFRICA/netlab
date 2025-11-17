<?php

namespace App\Services\Cisco;

class ResourcePoolService extends BaseCiscoApiService
{
    /**
     * Créer des resource pools
     */
    public function createResourcePools(array $data): array
    {
        return $this->post('/api/v0/resource_pools', $data);
    }

    /**
     * Obtenir tous les resource pools
     */
    public function getAllResourcePools(): array
    {
        return $this->get('/api/v0/resource_pools');
    }

    /**
     * Obtenir un resource pool spécifique
     */
    public function getResourcePool(string $resourcePoolId): array
    {
        return $this->get("/api/v0/resource_pools/{$resourcePoolId}");
    }

    /**
     * Mettre à jour un resource pool
     */
    public function updateResourcePool(string $resourcePoolId, array $data): array
    {
        return $this->patch("/api/v0/resource_pools/{$resourcePoolId}", $data);
    }

    /**
     * Supprimer un resource pool
     */
    public function deleteResourcePool(string $resourcePoolId): array
    {
        return $this->delete("/api/v0/resource_pools/{$resourcePoolId}");
    }

    /**
     * Obtenir l'utilisation des resource pools
     */
    public function getResourcePoolUsage(): array
    {
        return $this->get('/api/v0/resource_pool_usage');
    }

    /**
     * Obtenir l'utilisation d'un resource pool spécifique
     */
    public function getSingleResourcePoolUsage(string $resourcePoolId): array
    {
        return $this->get("/api/v0/resource_pool_usage/{$resourcePoolId}");
    }
}

