<?php

namespace App\Services\Cisco;

class ImportService extends BaseCiscoApiService
{
    /**
     * Créer un lab à partir d'une topologie au format CML2 YAML
     */
    public function importTopology(array $data): array
    {
        return $this->post('/api/v0/import', $data);
    }

    /**
     * Créer un lab à partir d'un fichier de topologie VIRL v1.x
     */
    public function importVirl1xTopology(array $data): array
    {
        return $this->post('/api/v0/import/virl-1x', $data);
    }

    /**
     * Importer un lab depuis un fichier YAML (avec upload)
     */
    public function importLabFromYaml(string $yamlContent, array $options = []): array
    {
        $data = array_merge([
            'topology' => $yamlContent
        ], $options);

        return $this->post('/api/v0/import', $data);
    }

    /**
     * Importer un lab depuis VIRL 1.x avec options
     */
    public function importFromVirl1x(string $topology, bool $updateIfExists = false): array
    {
        return $this->post('/api/v0/import/virl-1x', [
            'topology' => $topology,
            'update_if_exists' => $updateIfExists
        ]);
    }
}

