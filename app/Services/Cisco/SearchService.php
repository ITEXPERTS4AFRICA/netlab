<?php

namespace App\Services\Cisco;

class SearchService extends BaseCiscoApiService
{
    /**
     * Rechercher des labs avec critères multiples
     */
    public function searchLabs(array $criteria): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        
        return collect($labs)->filter(function($lab) use ($criteria) {
            return $this->matchLabCriteria($lab, $criteria);
        })->values()->all();
    }

    /**
     * Trouver des nodes par type dans un lab
     */
    public function findNodesByType(string $labId, string $nodeType): array
    {
        $nodes = $this->get("/v0/labs/{$labId}/nodes");
        
        return collect($nodes)->filter(function($node) use ($nodeType) {
            return ($node['node_definition'] ?? '') === $nodeType;
        })->values()->all();
    }

    /**
     * Rechercher par tags multiples
     */
    public function searchByTags(array $tags, string $mode = 'any'): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        
        return collect($labs)->filter(function($lab) use ($tags, $mode) {
            $labTags = $lab['tags'] ?? [];
            
            if ($mode === 'all') {
                return empty(array_diff($tags, $labTags));
            }
            
            return !empty(array_intersect($tags, $labTags));
        })->values()->all();
    }

    /**
     * Recherche textuelle globale
     */
    public function globalSearch(string $query): array
    {
        $query = strtolower($query);
        $labs = $this->get('/v0/labs?show_all=true');
        
        return collect($labs)->filter(function($lab) use ($query) {
            $searchable = implode(' ', [
                $lab['lab_title'] ?? '',
                $lab['lab_description'] ?? '',
                $lab['owner'] ?? '',
                implode(' ', $lab['tags'] ?? [])
            ]);
            
            return strpos(strtolower($searchable), $query) !== false;
        })->values()->all();
    }

    /**
     * Recherche avancée avec filtres complexes
     */
    public function advancedSearch(array $filters): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        
        return collect($labs)->filter(function($lab) use ($filters) {
            // Filtre par état
            if (isset($filters['state'])) {
                if (($lab['state'] ?? '') !== $filters['state']) {
                    return false;
                }
            }
            
            // Filtre par propriétaire
            if (isset($filters['owner'])) {
                if (($lab['owner'] ?? '') !== $filters['owner']) {
                    return false;
                }
            }
            
            // Filtre par nombre de nodes
            if (isset($filters['min_nodes'])) {
                if (count($lab['nodes'] ?? []) < $filters['min_nodes']) {
                    return false;
                }
            }
            
            if (isset($filters['max_nodes'])) {
                if (count($lab['nodes'] ?? []) > $filters['max_nodes']) {
                    return false;
                }
            }
            
            // Filtre par date de création
            if (isset($filters['created_after'])) {
                if (strtotime($lab['created'] ?? 0) < strtotime($filters['created_after'])) {
                    return false;
                }
            }
            
            // Filtre par date de modification
            if (isset($filters['modified_after'])) {
                if (strtotime($lab['modified'] ?? 0) < strtotime($filters['modified_after'])) {
                    return false;
                }
            }
            
            return true;
        })->values()->all();
    }

    /**
     * Trouver des labs par configuration réseau
     */
    public function findByNetworkConfig(array $config): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        
        return collect($labs)->filter(function($lab) use ($config) {
            // Recherche par subnet
            if (isset($config['subnet'])) {
                // Logique de recherche par subnet dans la topologie
            }
            
            // Recherche par protocole de routage
            if (isset($config['routing_protocol'])) {
                // Logique de recherche
            }
            
            return true;
        })->values()->all();
    }

    /**
     * Rechercher des nodes avec une configuration spécifique
     */
    public function findNodesWithConfig(string $labId, array $configCriteria): array
    {
        $nodes = $this->get("/v0/labs/{$labId}/nodes");
        
        return collect($nodes)->filter(function($node) use ($configCriteria) {
            foreach ($configCriteria as $key => $value) {
                if (($node['configuration'][$key] ?? null) !== $value) {
                    return false;
                }
            }
            return true;
        })->values()->all();
    }

    /**
     * Vérifier si un lab correspond aux critères
     */
    protected function matchLabCriteria(array $lab, array $criteria): bool
    {
        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'title':
                    if (stripos($lab['lab_title'] ?? '', $value) === false) {
                        return false;
                    }
                    break;
                    
                case 'owner':
                    if (($lab['owner'] ?? '') !== $value) {
                        return false;
                    }
                    break;
                    
                case 'state':
                    if (($lab['state'] ?? '') !== $value) {
                        return false;
                    }
                    break;
                    
                case 'tags':
                    $labTags = $lab['tags'] ?? [];
                    $searchTags = is_array($value) ? $value : [$value];
                    if (empty(array_intersect($labTags, $searchTags))) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }

    /**
     * Obtenir des suggestions de recherche
     */
    public function getSuggestions(string $partial): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        $suggestions = [];
        
        // Suggestions de titres
        foreach ($labs as $lab) {
            $title = $lab['lab_title'] ?? '';
            if (stripos($title, $partial) !== false) {
                $suggestions[] = [
                    'type' => 'title',
                    'value' => $title,
                    'lab_id' => $lab['id'] ?? ''
                ];
            }
        }
        
        return array_slice($suggestions, 0, 10);
    }

    /**
     * Compter les résultats par catégorie
     */
    public function facetedSearch(array $criteria): array
    {
        $results = $this->searchLabs($criteria);
        
        return [
            'results' => $results,
            'facets' => [
                'by_state' => $this->groupByField($results, 'state'),
                'by_owner' => $this->groupByField($results, 'owner'),
                'total' => count($results)
            ]
        ];
    }

    /**
     * Grouper par champ
     */
    protected function groupByField(array $items, string $field): array
    {
        $grouped = [];
        
        foreach ($items as $item) {
            $value = $item[$field] ?? 'unknown';
            $grouped[$value] = ($grouped[$value] ?? 0) + 1;
        }
        
        return $grouped;
    }
}

