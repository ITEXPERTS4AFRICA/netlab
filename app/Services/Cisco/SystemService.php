<?php

namespace App\Services\Cisco;

class SystemService extends BaseCiscoApiService
{
    /**
     * Obtenir les utilisateurs
     */
    public function getUsers(): array
    {
        return $this->get('/api/v0/users');
    }

    /**
     * Obtenir les appareils
     */
    public function getDevices(): array
    {
        return $this->get('/api/v0/devices');
    }

    /**
     * Obtenir la configuration d'authentification système
     */
    public function getSystemAuthConfig(): array
    {
        return $this->get('/api/v0/system/auth/config');
    }

    /**
     * Mettre à jour la configuration d'authentification système
     */
    public function updateSystemAuthConfig(array $data): array
    {
        return $this->patch('/api/v0/system/auth/config', $data);
    }

    /**
     * Définir la configuration d'authentification système
     */
    public function setSystemAuthConfig(array $data): array
    {
        return $this->put('/api/v0/system/auth/config', $data);
    }

    /**
     * Tester l'authentification système
     */
    public function testSystemAuth(array $data): array
    {
        return $this->post('/api/v0/system/auth/test', $data);
    }

    /**
     * Obtenir les groupes d'authentification système
     */
    public function getSystemAuthGroups(): array
    {
        return $this->get('/api/v0/system/auth/groups');
    }

    /**
     * Rafraîchir l'authentification système
     */
    public function refreshSystemAuth(): array
    {
        return $this->put('/api/v0/system/auth/refresh');
    }

    /**
     * Ajouter un dépôt de labs
     */
    public function addLabRepo(array $data): array
    {
        return $this->post('/api/v0/lab_repos', $data);
    }

    /**
     * Obtenir les dépôts de labs
     */
    public function getLabRepos(): array
    {
        return $this->get('/api/v0/lab_repos');
    }

    /**
     * Rafraîchir les dépôts de labs
     */
    public function refreshLabRepos(): array
    {
        return $this->put('/api/v0/lab_repos/refresh');
    }

    /**
     * Supprimer un dépôt de labs
     */
    public function deleteLabRepo(string $repoId): array
    {
        return $this->delete("/api/v0/lab_repos/{$repoId}");
    }

    /**
     * Obtenir les hôtes de calcul
     */
    public function getComputeHosts(): array
    {
        return $this->get('/api/v0/system/compute_hosts');
    }

    /**
     * Obtenir la configuration des hôtes de calcul
     */
    public function getComputeHostsConfiguration(): array
    {
        return $this->get('/api/v0/system/compute_hosts/configuration');
    }

    /**
     * Mettre à jour la configuration des hôtes de calcul
     */
    public function updateComputeHostsConfiguration(array $data): array
    {
        return $this->patch('/api/v0/system/compute_hosts/configuration', $data);
    }

    /**
     * Obtenir un hôte de calcul spécifique
     */
    public function getComputeHost(string $computeId): array
    {
        return $this->get("/api/v0/system/compute_hosts/{$computeId}");
    }

    /**
     * Mettre à jour un hôte de calcul
     */
    public function updateComputeHost(string $computeId, array $data): array
    {
        return $this->patch("/api/v0/system/compute_hosts/{$computeId}", $data);
    }

    /**
     * Supprimer un hôte de calcul
     */
    public function deleteComputeHost(string $computeId): array
    {
        return $this->delete("/api/v0/system/compute_hosts/{$computeId}");
    }

    /**
     * Obtenir les connecteurs externes
     */
    public function getExternalConnectors(): array
    {
        return $this->get('/api/v0/system/external_connectors');
    }

    /**
     * Mettre à jour les connecteurs externes
     */
    public function updateExternalConnectors(): array
    {
        return $this->put('/api/v0/system/external_connectors');
    }

    /**
     * Obtenir un connecteur externe spécifique
     */
    public function getExternalConnector(string $connectorId): array
    {
        return $this->get("/api/v0/system/external_connectors/{$connectorId}");
    }

    /**
     * Mettre à jour un connecteur externe
     */
    public function updateExternalConnector(string $connectorId, array $data): array
    {
        return $this->patch("/api/v0/system/external_connectors/{$connectorId}", $data);
    }

    /**
     * Supprimer un connecteur externe
     */
    public function deleteExternalConnector(string $connectorId): array
    {
        return $this->delete("/api/v0/system/external_connectors/{$connectorId}");
    }

    /**
     * Obtenir le mode de maintenance
     */
    public function getMaintenanceMode(): array
    {
        return $this->get('/api/v0/system/maintenance_mode');
    }

    /**
     * Mettre à jour le mode de maintenance
     */
    public function updateMaintenanceMode(array $data): array
    {
        return $this->patch('/api/v0/system/maintenance_mode', $data);
    }

    /**
     * Ajouter une notice système
     */
    public function addSystemNotice(array $data): array
    {
        return $this->post('/api/v0/system/notices', $data);
    }

    /**
     * Obtenir les notices système
     */
    public function getSystemNotices(): array
    {
        return $this->get('/api/v0/system/notices');
    }

    /**
     * Obtenir une notice système spécifique
     */
    public function getSystemNotice(string $noticeId): array
    {
        return $this->get("/api/v0/system/notices/{$noticeId}");
    }

    /**
     * Mettre à jour une notice système
     */
    public function updateSystemNotice(string $noticeId, array $data): array
    {
        return $this->patch("/api/v0/system/notices/{$noticeId}", $data);
    }

    /**
     * Supprimer une notice système
     */
    public function deleteSystemNotice(string $noticeId): array
    {
        return $this->delete("/api/v0/system/notices/{$noticeId}");
    }

    /**
     * Obtenir l'archive système
     */
    public function getSystemArchive()
    {
        $response = \Illuminate\Support\Facades\Http::withToken($this->token)
            ->withOptions(['verify' => false])
            ->get("{$this->baseUrl}/api/v0/system_archive");

        return $this->handleRawResponse($response, 'Unable to get system archive');
    }

    /**
     * Obtenir la santé du système
     */
    public function getSystemHealth(): array
    {
        return $this->get('/api/v0/system_health');
    }

    /**
     * Obtenir les statistiques système
     */
    public function getSystemStats(): array
    {
        return $this->get('/api/v0/system_stats');
    }

    /**
     * Obtenir les informations système
     */
    public function getSystemInformation(): array
    {
        $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/json'])
            ->get("{$this->baseUrl}/api/v0/system_information");

        return $this->handleResponse($response, 'Unable to get system information');
    }

    /**
     * Obtenir toutes les clés console
     */
    public function getAllConsoleKeys(): array
    {
        return $this->get('/api/v0/keys/console');
    }

    /**
     * Obtenir toutes les clés VNC
     */
    public function getAllVncKeys(): array
    {
        return $this->get('/api/v0/keys/vnc');
    }
}

