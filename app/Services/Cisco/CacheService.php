<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * TTL (Time To Live) par type de ressource en secondes
     */
    protected array $ttl = [
        'labs' => 300,           // 5 minutes
        'lab' => 120,            // 2 minutes
        'nodes' => 60,           // 1 minute
        'node' => 30,            // 30 secondes
        'state' => 10,           // 10 secondes (état change souvent)
        'topology' => 600,       // 10 minutes
        'users' => 900,          // 15 minutes
        'groups' => 600,         // 10 minutes
        'images' => 1800,        // 30 minutes
        'licensing' => 3600,     // 1 heure
        'system' => 1800,        // 30 minutes
    ];

    protected string $prefix = 'cml:';

    /**
     * Récupérer ou exécuter avec cache
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cacheKey = $this->prefix . $key;
        
        if (config('app.debug')) {
            // En mode debug, on peut désactiver le cache
            if (request()->header('X-No-Cache')) {
                return $callback();
            }
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Récupérer avec TTL automatique selon le type
     */
    public function rememberByType(string $type, string $key, callable $callback)
    {
        $ttl = $this->ttl[$type] ?? 300;
        return $this->remember("{$type}:{$key}", $ttl, $callback);
    }

    /**
     * Invalider le cache pour une clé spécifique
     */
    public function forget(string $key): bool
    {
        return Cache::forget($this->prefix . $key);
    }

    /**
     * Invalider tous les caches d'un lab
     */
    public function invalidateLab(string $labId): void
    {
        $patterns = [
            "lab:{$labId}",
            "labs:*",
            "nodes:{$labId}:*",
            "links:{$labId}:*",
            "topology:{$labId}",
            "state:{$labId}:*",
        ];

        foreach ($patterns as $pattern) {
            $this->forgetPattern($pattern);
        }
    }

    /**
     * Invalider tous les caches d'un node
     */
    public function invalidateNode(string $labId, string $nodeId): void
    {
        $patterns = [
            "node:{$labId}:{$nodeId}",
            "nodes:{$labId}",
            "state:{$labId}:{$nodeId}",
            "topology:{$labId}",
        ];

        foreach ($patterns as $pattern) {
            $this->forgetPattern($pattern);
        }
    }

    /**
     * Invalider selon un pattern (avec wildcard)
     */
    protected function forgetPattern(string $pattern): void
    {
        $fullPattern = $this->prefix . $pattern;
        
        // Pour Redis
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Cache::getRedis()->keys($fullPattern);
            foreach ($keys as $key) {
                Cache::forget(str_replace($this->prefix, '', $key));
            }
        } else {
            // Pour les autres drivers, on oublie directement
            Cache::forget($fullPattern);
        }
    }

    /**
     * Vider tout le cache CML
     */
    public function flush(): void
    {
        $this->forgetPattern('*');
    }

    /**
     * Récupérer ou mettre en cache avec tags (pour Laravel 9+)
     */
    public function rememberWithTags(array $tags, string $key, int $ttl, callable $callback)
    {
        if (method_exists(Cache::class, 'tags')) {
            return Cache::tags(array_map(fn($tag) => $this->prefix . $tag, $tags))
                ->remember($key, $ttl, $callback);
        }
        
        return $this->remember($key, $ttl, $callback);
    }

    /**
     * Invalider par tags
     */
    public function flushTags(array $tags): void
    {
        if (method_exists(Cache::class, 'tags')) {
            Cache::tags(array_map(fn($tag) => $this->prefix . $tag, $tags))->flush();
        }
    }

    /**
     * Obtenir les statistiques de cache
     */
    public function getStats(): array
    {
        // Statistiques basiques
        return [
            'prefix' => $this->prefix,
            'driver' => config('cache.default'),
            'ttl_config' => $this->ttl,
        ];
    }

    /**
     * Réchauffer le cache (warm-up)
     */
    public function warmUp(array $resources = []): array
    {
        $warmed = [];
        
        // Exemple : précacher les ressources fréquentes
        if (in_array('labs', $resources)) {
            $warmed['labs'] = 'Warmed up';
        }
        
        return $warmed;
    }

    /**
     * Définir un TTL personnalisé pour un type
     */
    public function setTtl(string $type, int $seconds): void
    {
        $this->ttl[$type] = $seconds;
    }

    /**
     * Obtenir le TTL pour un type
     */
    public function getTtl(string $type): int
    {
        return $this->ttl[$type] ?? 300;
    }
}

