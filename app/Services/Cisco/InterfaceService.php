<?php

namespace App\Services\Cisco;

class InterfaceService extends BaseCiscoApiService
{
    /**
     * Obtenir une interface spécifique
     */
    public function getInterface(string $labId, string $interfaceId): array
    {
        return $this->get("/api/v0/labs/{$labId}/interfaces/{$interfaceId}");
    }

    /**
     * Mettre à jour une interface
     */
    public function updateInterface(string $labId, string $interfaceId, array $data): array
    {
        return $this->patch("/api/v0/labs/{$labId}/interfaces/{$interfaceId}", $data);
    }

    /**
     * Supprimer une interface
     */
    public function deleteInterface(string $labId, string $interfaceId): array
    {
        return $this->delete("/api/v0/labs/{$labId}/interfaces/{$interfaceId}");
    }

    /**
     * Obtenir l'état d'une interface
     */
    public function getInterfaceState(string $labId, string $interfaceId): array
    {
        return $this->get("/api/v0/labs/{$labId}/interfaces/{$interfaceId}/state");
    }

    /**
     * Démarrer une interface
     */
    public function startInterface(string $labId, string $interfaceId): array
    {
        return $this->put("/api/v0/labs/{$labId}/interfaces/{$interfaceId}/state/start");
    }

    /**
     * Arrêter une interface
     */
    public function stopInterface(string $labId, string $interfaceId): array
    {
        return $this->put("/api/v0/labs/{$labId}/interfaces/{$interfaceId}/state/stop");
    }
}

