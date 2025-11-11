<?php

namespace App\Services\Cisco;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TemplateService extends BaseCiscoApiService
{
    protected string $disk = 'local';
    protected string $templatesPath = 'cml_templates';

    /**
     * Sauvegarder un lab comme template
     */
    public function saveAsTemplate(string $labId, array $metadata): array
    {
        // Récupérer la topologie du lab
        $topology = $this->get("/v0/labs/{$labId}/topology");
        $labInfo = $this->get("/v0/labs/{$labId}");

        $template = [
            'id' => Str::uuid()->toString(),
            'name' => $metadata['name'] ?? $labInfo['lab_title'] ?? 'Unnamed Template',
            'description' => $metadata['description'] ?? '',
            'category' => $metadata['category'] ?? 'general',
            'tags' => $metadata['tags'] ?? [],
            'topology' => $topology,
            'lab_info' => [
                'title' => $labInfo['lab_title'] ?? '',
                'description' => $labInfo['lab_description'] ?? '',
                'nodes_count' => count($labInfo['nodes'] ?? []),
                'links_count' => count($labInfo['links'] ?? []),
            ],
            'created_at' => now()->toIso8601String(),
            'created_by' => auth()->id() ?? 'system',
        ];

        // Sauvegarder le template
        $filename = $template['id'] . '.json';
        Storage::disk($this->disk)->put(
            "{$this->templatesPath}/{$filename}",
            json_encode($template, JSON_PRETTY_PRINT)
        );

        return $template;
    }

    /**
     * Créer un lab à partir d'un template
     */
    public function createLabFromTemplate(string $templateId, array $overrides = []): array
    {
        $template = $this->getTemplate($templateId);

        if (!$template) {
            throw new \Exception("Template {$templateId} not found");
        }

        // Préparer les données du lab
        $labData = $template['topology'];
        
        // Appliquer les overrides
        if (isset($overrides['title'])) {
            $labData['lab_title'] = $overrides['title'];
        }
        
        if (isset($overrides['description'])) {
            $labData['lab_description'] = $overrides['description'];
        }

        // Créer le lab via l'API
        $result = $this->post('/v0/import', ['topology' => json_encode($labData)]);

        return $result;
    }

    /**
     * Obtenir un template par ID
     */
    public function getTemplate(string $templateId): ?array
    {
        $filename = $templateId . '.json';
        $path = "{$this->templatesPath}/{$filename}";

        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $content = Storage::disk($this->disk)->get($path);
        return json_decode($content, true);
    }

    /**
     * Lister tous les templates
     */
    public function listTemplates(array $filters = []): array
    {
        $files = Storage::disk($this->disk)->files($this->templatesPath);
        $templates = [];

        foreach ($files as $file) {
            $content = Storage::disk($this->disk)->get($file);
            $template = json_decode($content, true);

            // Appliquer les filtres
            if ($this->matchesFilters($template, $filters)) {
                $templates[] = $template;
            }
        }

        return $templates;
    }

    /**
     * Supprimer un template
     */
    public function deleteTemplate(string $templateId): bool
    {
        $filename = $templateId . '.json';
        $path = "{$this->templatesPath}/{$filename}";

        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Mettre à jour les métadonnées d'un template
     */
    public function updateTemplateMetadata(string $templateId, array $metadata): array
    {
        $template = $this->getTemplate($templateId);

        if (!$template) {
            throw new \Exception("Template {$templateId} not found");
        }

        // Mettre à jour les métadonnées
        $template['name'] = $metadata['name'] ?? $template['name'];
        $template['description'] = $metadata['description'] ?? $template['description'];
        $template['category'] = $metadata['category'] ?? $template['category'];
        $template['tags'] = $metadata['tags'] ?? $template['tags'];
        $template['updated_at'] = now()->toIso8601String();

        // Sauvegarder
        $filename = $templateId . '.json';
        Storage::disk($this->disk)->put(
            "{$this->templatesPath}/{$filename}",
            json_encode($template, JSON_PRETTY_PRINT)
        );

        return $template;
    }

    /**
     * Partager un template avec d'autres utilisateurs
     */
    public function shareTemplate(string $templateId, array $userIds): array
    {
        $template = $this->getTemplate($templateId);

        if (!$template) {
            throw new \Exception("Template {$templateId} not found");
        }

        $template['shared_with'] = array_unique(array_merge(
            $template['shared_with'] ?? [],
            $userIds
        ));

        // Sauvegarder
        $filename = $templateId . '.json';
        Storage::disk($this->disk)->put(
            "{$this->templatesPath}/{$filename}",
            json_encode($template, JSON_PRETTY_PRINT)
        );

        return $template;
    }

    /**
     * Créer plusieurs labs à partir d'un template
     */
    public function createMultipleLabsFromTemplate(string $templateId, array $labsConfig): array
    {
        $results = [];

        foreach ($labsConfig as $config) {
            try {
                $results[] = $this->createLabFromTemplate($templateId, $config);
            } catch (\Exception $e) {
                $results[] = ['error' => $e->getMessage(), 'config' => $config];
            }
        }

        return $results;
    }

    /**
     * Exporter un template en YAML
     */
    public function exportTemplateAsYaml(string $templateId): string
    {
        $template = $this->getTemplate($templateId);

        if (!$template) {
            throw new \Exception("Template {$templateId} not found");
        }

        return \Symfony\Component\Yaml\Yaml::dump($template['topology'], 4);
    }

    /**
     * Importer un template depuis YAML
     */
    public function importTemplateFromYaml(string $yaml, array $metadata): array
    {
        $topology = \Symfony\Component\Yaml\Yaml::parse($yaml);

        $template = [
            'id' => Str::uuid()->toString(),
            'name' => $metadata['name'] ?? 'Imported Template',
            'description' => $metadata['description'] ?? '',
            'category' => $metadata['category'] ?? 'imported',
            'tags' => $metadata['tags'] ?? ['imported'],
            'topology' => $topology,
            'created_at' => now()->toIso8601String(),
            'created_by' => auth()->id() ?? 'system',
        ];

        // Sauvegarder
        $filename = $template['id'] . '.json';
        Storage::disk($this->disk)->put(
            "{$this->templatesPath}/{$filename}",
            json_encode($template, JSON_PRETTY_PRINT)
        );

        return $template;
    }

    /**
     * Vérifier si un template correspond aux filtres
     */
    protected function matchesFilters(array $template, array $filters): bool
    {
        if (isset($filters['category']) && $template['category'] !== $filters['category']) {
            return false;
        }

        if (isset($filters['tags'])) {
            $templateTags = $template['tags'] ?? [];
            $filterTags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            
            if (!array_intersect($templateTags, $filterTags)) {
                return false;
            }
        }

        if (isset($filters['search'])) {
            $search = strtolower($filters['search']);
            $name = strtolower($template['name'] ?? '');
            $description = strtolower($template['description'] ?? '');
            
            if (strpos($name, $search) === false && strpos($description, $search) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtenir les statistiques des templates
     */
    public function getTemplateStats(): array
    {
        $templates = $this->listTemplates();

        $stats = [
            'total' => count($templates),
            'by_category' => [],
            'by_tags' => [],
        ];

        foreach ($templates as $template) {
            // Par catégorie
            $category = $template['category'] ?? 'uncategorized';
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;

            // Par tags
            foreach ($template['tags'] ?? [] as $tag) {
                $stats['by_tags'][$tag] = ($stats['by_tags'][$tag] ?? 0) + 1;
            }
        }

        return $stats;
    }
}

