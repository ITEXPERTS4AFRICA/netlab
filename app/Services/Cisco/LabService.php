<?php

namespace App\Services\Cisco;

class LabService extends BaseCiscoApiService
{
    /**
     * Obtenir tous les labs (avec cache)
     */
    public function getLabs(): array
    {
        return $this->getCached(
            '/api/v0/labs?show_all=true',
            'labs:all',
            $this->cache->getTtl('labs')
        );
    }

    /**
     * Obtenir un lab spécifique (avec cache)
     */
    public function getLab(string $id): array
    {
        return $this->getCached(
            "/api/v0/labs/{$id}",
            "lab:{$id}",
            $this->cache->getTtl('lab')
        );
    }

    /**
     * Démarrer un lab
     */
    public function startLab(string $id): array
    {
        $result = $this->put("/api/v0/labs/{$id}/start");
        $this->cache->invalidateLab($id);
        return $result;
    }

    /**
     * Arrêter un lab
     */
    public function stopLab(string $id): array
    {
        $result = $this->put("/api/v0/labs/{$id}/stop");
        $this->cache->invalidateLab($id);
        return $result;
    }

    /**
     * Réinitialiser un lab
     */
    public function wipeLab(string $id): array
    {
        $result = $this->put("/api/v0/labs/{$id}/wipe");
        $this->cache->invalidateLab($id);
        return $result;
    }

    /**
     * Supprimer un lab
     */
    public function deleteLab(string $id): array
    {
        $result = $this->delete("/api/v0/labs/{$id}");
        $this->cache->invalidateLab($id);
        $this->cache->forget('labs:all');
        return $result;
    }

    /**
     * Mettre à jour un lab
     */
    public function updateLab(string $id, array $data): array
    {
        return $this->patch("/api/v0/labs/{$id}", $data);
    }

    /**
     * Obtenir l'état d'un lab
     */
    public function getLabState(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/state");
    }

    /**
     * Obtenir le testbed pyATS
     */
    public function getPyatsTestbed(string $id)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($this->getToken())
            ->withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/yaml'])
            ->get("{$this->baseUrl}/api/v0/labs/{$id}/pyats_testbed");

        return $this->handleRawResponse($response, 'Unable to fetch pyATS testbed');
    }

    /**
     * Vérifier si le lab a convergé
     */
    public function checkIfConverged(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/check_if_converged");
    }

    /**
     * Obtenir la topologie d'un lab
     */
    public function getTopology(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/topology");
    }

    /**
     * Obtenir le schéma d'un lab
     */
    public function getLabSchema(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/topology");
    }

    /**
     * Télécharger un lab
     */
    public function downloadLab(string $id)
    {
        $response = \Illuminate\Support\Facades\Http::withToken($this->getToken())
            ->withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/yaml'])
            ->get("{$this->baseUrl}/api/v0/labs/{$id}/download");

        return $this->handleRawResponse($response, 'Unable to download lab');
    }

    /**
     * Obtenir les annotations d'un lab
     */
    public function getLabAnnotations(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/annotations");
    }

    /**
     * Créer une annotation de lab
     */
    public function createLabAnnotation(string $id, array $data): array
    {
        return $this->post("/api/v0/labs/{$id}/annotations", $data);
    }

    /**
     * Mettre à jour une annotation de lab
     */
    public function updateLabAnnotation(string $id, string $annotationId, array $data): array
    {
        return $this->patch("/api/v0/labs/{$id}/annotations/{$annotationId}", $data);
    }

    /**
     * Supprimer une annotation de lab
     */
    public function deleteLabAnnotation(string $id, string $annotationId): array
    {
        return $this->delete("/api/v0/labs/{$id}/annotations/{$annotationId}");
    }

    /**
     * Obtenir les événements d'un lab
     */
    public function getLabEvents(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/events");
    }

    /**
     * Obtenir les mappings de connecteurs
     */
    public function getConnectorMappings(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/connector_mappings");
    }

    /**
     * Mettre à jour les mappings de connecteurs
     */
    public function updateConnectorMappings(string $id, array $data): array
    {
        return $this->patch("/api/v0/labs/{$id}/connector_mappings", $data);
    }

    /**
     * Obtenir les resource pools d'un lab
     */
    public function getResourcePools(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/resource_pools");
    }

    /**
     * Trouver un node par label
     */
    public function findNodeByLabel(string $id, string $searchQuery): array
    {
        return $this->get("/api/v0/labs/{$id}/find/node/label/{$searchQuery}");
    }

    /**
     * Trouver des nodes par tag
     */
    public function findNodesByTag(string $id, string $searchQuery): array
    {
        return $this->get("/api/v0/labs/{$id}/find_all/node/tag/{$searchQuery}");
    }

    /**
     * Obtenir l'état des éléments du lab
     */
    public function getLabElementState(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/lab_element_state");
    }

    /**
     * Obtenir les statistiques de simulation
     */
    public function getSimulationStats(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/simulation_stats");
    }

    /**
     * Obtenir les informations de tile du lab
     */
    public function getLabTile(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/tile");
    }

    /**
     * Créer des interfaces
     */
    public function createInterfaces(string $id, array $data): array
    {
        return $this->post("/api/v0/labs/{$id}/interfaces", $data);
    }

    /**
     * Obtenir les smart annotations
     */
    public function getSmartAnnotations(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/smart_annotations");
    }

    /**
     * Obtenir une smart annotation spécifique
     */
    public function getSmartAnnotation(string $id, string $smartAnnotationId): array
    {
        return $this->get("/api/v0/labs/{$id}/smart_annotations/{$smartAnnotationId}");
    }

    /**
     * Mettre à jour une smart annotation
     */
    public function updateSmartAnnotation(string $id, string $smartAnnotationId, array $data): array
    {
        return $this->patch("/api/v0/labs/{$id}/smart_annotations/{$smartAnnotationId}", $data);
    }

    /**
     * Obtenir les labs d'exemple
     */
    public function getSampleLabs(): array
    {
        return $this->get('/api/v0/sample/labs');
    }

    /**
     * Obtenir un lab d'exemple spécifique
     */
    public function getSampleLab(string $labId): array
    {
        return $this->get("/api/v0/sample/labs/{$labId}");
    }

    /**
     * Créer un nouveau lab
     */
    public function createLab(array $data): array
    {
        return $this->post('/api/v0/labs', $data);
    }

    /**
     * Obtenir les groupes associés à un lab
     */
    public function getLabGroups(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/groups");
    }

    /**
     * Modifier les groupes associés à un lab
     */
    public function updateLabGroups(string $id, array $data): array
    {
        return $this->put("/api/v0/labs/{$id}/groups", $data);
    }

    /**
     * Générer les configurations bootstrap pour les nodes du lab
     */
    public function bootstrapLab(string $id, array $data = []): array
    {
        return $this->put("/api/v0/labs/{$id}/bootstrap", $data);
    }

    /**
     * Obtenir les associations lab/groupe et lab/utilisateur
     */
    public function getLabAssociations(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/associations");
    }

    /**
     * Mettre à jour les associations lab/groupe et lab/utilisateur
     */
    public function updateLabAssociations(string $id, array $data): array
    {
        return $this->patch("/api/v0/labs/{$id}/associations", $data);
    }

    /**
     * Obtenir les adresses Layer 3 de tous les nodes du lab
     */
    public function getLabLayer3Addresses(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/layer3_addresses");
    }

    /**
     * Obtenir les configurations build pour les nodes
     */
    public function getBuildConfigurations(): array
    {
        return $this->get('/api/v0/build_configurations');
    }

    /**
     * Obtenir toutes les interfaces du lab
     */
    public function getLabInterfaces(string $id): array
    {
        return $this->get("/api/v0/labs/{$id}/interfaces");
    }
}

