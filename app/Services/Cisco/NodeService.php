<?php

namespace App\Services\Cisco;

class NodeService extends BaseCiscoApiService
{
    /**
     * Ajouter un node
     */
    public function addNode(string $labId, array $data): array
    {
        return $this->post("/api/v0/labs/{$labId}/nodes", $data);
    }

    /**
     * Obtenir les nodes d'un lab
     * 
     * @param string $labId L'ID du lab
     * @param bool $withData Si true, retourne les détails complets des nodes au lieu de juste les UUIDs
     */
    public function getLabNodes(string $labId, bool $withData = true): array
    {
        $endpoint = "/api/v0/labs/{$labId}/nodes";
        if ($withData) {
            // Ajouter le paramètre data=true pour obtenir les détails complets
            $endpoint .= "?data=true";
        }
        return $this->get($endpoint);
    }

    /**
     * Obtenir un node spécifique
     */
    public function getNode(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}");
    }

    /**
     * Mettre à jour un node
     */
    public function updateNode(string $labId, string $nodeId, array $data): array
    {
        return $this->patch("/api/v0/labs/{$labId}/nodes/{$nodeId}", $data);
    }

    /**
     * Supprimer un node
     */
    public function deleteNode(string $labId, string $nodeId): array
    {
        return $this->delete("/api/v0/labs/{$labId}/nodes/{$nodeId}");
    }

    /**
     * Réinitialiser les disques d'un node
     */
    public function wipeNodeDisks(string $labId, string $nodeId): array
    {
        return $this->put("/api/v0/labs/{$labId}/nodes/{$nodeId}/wipe_disks");
    }

    /**
     * Extraire la configuration d'un node
     */
    public function extractNodeConfiguration(string $labId, string $nodeId): array
    {
        return $this->put("/api/v0/labs/{$labId}/nodes/{$nodeId}/extract_configuration");
    }

    /**
     * Obtenir l'état d'un node
     */
    public function getNodeState(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/state");
    }

    /**
     * Démarrer un node
     */
    public function startNode(string $labId, string $nodeId): array
    {
        return $this->put("/api/v0/labs/{$labId}/nodes/{$nodeId}/state/start");
    }

    /**
     * Arrêter un node
     */
    public function stopNode(string $labId, string $nodeId): array
    {
        return $this->put("/api/v0/labs/{$labId}/nodes/{$nodeId}/state/stop");
    }

    /**
     * Vérifier si un node a convergé
     */
    public function checkNodeIfConverged(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/check_if_converged");
    }

    /**
     * Obtenir la clé VNC d'un node
     */
    public function getNodeVncKey(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/keys/vnc");
    }

    /**
     * Obtenir la clé console d'un node
     */
    public function getNodeConsoleKey(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/keys/console");
    }

    /**
     * Obtenir le log console
     */
    public function getConsoleLog(string $labId, string $nodeId, string $consoleId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/consoles/{$consoleId}/log");
    }

    /**
     * Cloner l'image d'un node
     */
    public function cloneNodeImage(string $labId, string $nodeId): array
    {
        return $this->put("/api/v0/labs/{$labId}/nodes/{$nodeId}/clone_image");
    }

    /**
     * Obtenir tous les nodes
     */
    public function getAllNodes(): array
    {
        return $this->get('/api/v0/nodes');
    }

    /**
     * Obtenir les adresses Layer 3 d'un node
     */
    public function getNodeLayer3Addresses(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/layer3_addresses");
    }

    /**
     * Obtenir les interfaces d'un node
     */
    public function getNodeInterfaces(string $labId, string $nodeId): array
    {
        return $this->get("/api/v0/labs/{$labId}/nodes/{$nodeId}/interfaces");
    }

    /**
     * Obtenir les définitions de nodes
     */
    public function getNodeDefinitions(): array
    {
        return $this->get('/api/v0/node_definitions');
    }

    /**
     * Créer une définition de node
     */
    public function createNodeDefinition(array $data): array
    {
        return $this->post('/api/v0/node_definitions', $data);
    }

    /**
     * Mettre à jour une définition de node
     */
    public function updateNodeDefinition(array $data): array
    {
        return $this->put('/api/v0/node_definitions', $data);
    }

    /**
     * Obtenir une définition de node spécifique
     */
    public function getNodeDefinition(string $defId): array
    {
        return $this->get("/api/v0/node_definitions/{$defId}");
    }

    /**
     * Supprimer une définition de node
     */
    public function deleteNodeDefinition(string $defId): array
    {
        return $this->delete("/api/v0/node_definitions/{$defId}");
    }

    /**
     * Définir une définition de node en lecture seule
     */
    public function setNodeDefinitionReadOnly(string $defId): array
    {
        return $this->put("/api/v0/node_definitions/{$defId}/read_only");
    }

    /**
     * Obtenir les définitions de nodes simplifiées
     */
    public function getSimplifiedNodeDefinitions(): array
    {
        return $this->get('/api/v0/simplified_node_definitions');
    }

    /**
     * Obtenir le schéma de définition de node
     */
    public function getNodeDefinitionSchema(): array
    {
        return $this->get('/api/v0/node_definition_schema');
    }

    /**
     * Obtenir les définitions d'images pour une définition de node
     */
    public function getNodeDefinitionsImageDefinitions(string $defId): array
    {
        return $this->get("/api/v0/node_definitions/{$defId}/image_definitions");
    }
}

