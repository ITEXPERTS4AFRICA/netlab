<?php

namespace App\Services\Cisco;

class ImageService extends BaseCiscoApiService
{
    /**
     * Uploader une image
     */
    public function uploadImage(array $data): array
    {
        return $this->post('/api/v0/images/upload', $data);
    }

    /**
     * Supprimer une image gérée
     */
    public function deleteManagedImage(string $filename): array
    {
        return $this->delete("/api/v0/images/manage/{$filename}");
    }

    /**
     * Obtenir le schéma de définition d'image
     */
    public function getImageDefinitionSchema(): array
    {
        return $this->get('/api/v0/image_definition_schema');
    }

    /**
     * Lister le dossier de dépôt de définition d'image
     */
    public function getListImageDefinitionDropFolder(): array
    {
        return $this->get('/api/v0/list_image_definition_drop_folder');
    }

    /**
     * Obtenir les définitions d'images
     */
    public function getImageDefinitions(): array
    {
        return $this->get('/api/v0/image_definitions');
    }

    /**
     * Créer une définition d'image
     */
    public function createImageDefinition(array $data): array
    {
        return $this->post('/api/v0/image_definitions', $data);
    }

    /**
     * Mettre à jour une définition d'image
     */
    public function updateImageDefinition(array $data): array
    {
        return $this->put('/api/v0/image_definitions', $data);
    }

    /**
     * Obtenir une définition d'image spécifique
     */
    public function getImageDefinition(string $defId): array
    {
        return $this->get("/api/v0/image_definitions/{$defId}");
    }

    /**
     * Supprimer une définition d'image
     */
    public function deleteImageDefinition(string $defId): array
    {
        return $this->delete("/api/v0/image_definitions/{$defId}");
    }

    /**
     * Définir une définition d'image en lecture seule
     */
    public function setImageDefinitionReadOnly(string $defId): array
    {
        return $this->put("/api/v0/image_definitions/{$defId}/read_only");
    }
}

