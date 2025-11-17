<?php

namespace App\Services\Cisco;

class GroupService extends BaseCiscoApiService
{
    /**
     * Obtenir la liste de tous les groupes disponibles
     */
    public function getGroups(): array
    {
        return $this->get('/api/v0/groups');
    }

    /**
     * Créer un nouveau groupe
     */
    public function createGroup(array $data): array
    {
        return $this->post('/api/v0/groups', $data);
    }

    /**
     * Obtenir les informations d'un groupe spécifique
     */
    public function getGroup(string $groupId): array
    {
        return $this->get("/api/v0/groups/{$groupId}");
    }

    /**
     * Supprimer un groupe
     */
    public function deleteGroup(string $groupId): array
    {
        return $this->delete("/api/v0/groups/{$groupId}");
    }

    /**
     * Mettre à jour un groupe
     */
    public function updateGroup(string $groupId, array $data): array
    {
        return $this->patch("/api/v0/groups/{$groupId}", $data);
    }

    /**
     * Obtenir la liste des labs d'un groupe
     */
    public function getGroupLabs(string $groupId): array
    {
        return $this->get("/api/v0/groups/{$groupId}/labs");
    }

    /**
     * Obtenir la liste des membres d'un groupe
     */
    public function getGroupMembers(string $groupId): array
    {
        return $this->get("/api/v0/groups/{$groupId}/members");
    }

    /**
     * Obtenir l'identifiant unique d'un groupe par son nom
     */
    public function getGroupUuidByName(string $groupName): array
    {
        return $this->get("/api/v0/groups/{$groupName}/id");
    }
}

