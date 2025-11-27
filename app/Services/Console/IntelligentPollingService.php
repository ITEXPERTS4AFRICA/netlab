<?php

namespace App\Services\Console;

use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de polling intelligent pour les consoles CML
 * 
 * Fonctionnalités :
 * - Cache intelligent des logs pour éviter les doublons
 * - Détection automatique des prompts IOS
 * - Parsing des résultats structurés
 * - Optimisation des requêtes avec rate limiting
 */
class IntelligentPollingService
{
    protected CiscoApiService $cisco;
    protected int $pollInterval = 2000; // ms
    protected int $maxCacheSize = 1000; // lignes
    protected int $rateLimitPerMinute = 30; // requêtes max par minute

    public function __construct(CiscoApiService $cisco)
    {
        $this->cisco = $cisco;
    }

    /**
     * Récupérer les logs d'une console avec cache intelligent
     */
    public function getConsoleLogs(string $labId, string $nodeId, string $consoleId): array
    {
        $cacheKey = "console_logs:{$labId}:{$nodeId}:{$consoleId}";
        $rateLimitKey = "console_rate_limit:{$labId}:{$nodeId}:{$consoleId}";

        // Vérifier le rate limiting
        $requestCount = Cache::get($rateLimitKey, 0);
        if ($requestCount >= $this->rateLimitPerMinute) {
            Log::warning('Console polling rate limit atteint', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'requests' => $requestCount,
            ]);

            return [
                'error' => 'Rate limit atteint. Veuillez patienter.',
                'cached_logs' => Cache::get($cacheKey, []),
                'rate_limited' => true,
            ];
        }

        // Incrémenter le compteur de rate limiting
        Cache::put($rateLimitKey, $requestCount + 1, now()->addMinute());

        try {
            // Récupérer les logs depuis CML
            $response = $this->cisco->console->getConsoleLog($labId, $nodeId, $consoleId);

            if (isset($response['error'])) {
                Log::warning('Erreur lors de la récupération des logs', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'console_id' => $consoleId,
                    'error' => $response['error'],
                    'status' => $response['status'] ?? null,
                    'is_timeout' => $response['is_timeout'] ?? false,
                ]);

                // Retourner les logs en cache même en cas d'erreur
                $cachedLogs = Cache::get($cacheKey, []);
                
                return [
                    'error' => $response['error'],
                    'cached_logs' => $cachedLogs,
                    'status' => $response['status'] ?? 500,
                    'is_timeout' => $response['is_timeout'] ?? false,
                ];
            }

            // Normaliser les logs
            $logs = $this->normalizeLogs($response);

            // Récupérer les logs en cache
            $cachedLogs = Cache::get($cacheKey, []);

            // Détecter les nouvelles lignes
            $newLogs = $this->detectNewLogs($cachedLogs, $logs);

            // Mettre à jour le cache
            $updatedLogs = array_merge($cachedLogs, $newLogs);
            
            // Limiter la taille du cache
            if (count($updatedLogs) > $this->maxCacheSize) {
                $updatedLogs = array_slice($updatedLogs, -$this->maxCacheSize);
            }

            Cache::put($cacheKey, $updatedLogs, now()->addHours(1));

            // Parser les logs pour détecter les prompts IOS
            $parsedLogs = $this->parseIOSLogs($updatedLogs);

            return [
                'success' => true,
                'logs' => $updatedLogs,
                'new_logs' => $newLogs,
                'parsed' => $parsedLogs,
                'total_lines' => count($updatedLogs),
                'new_lines' => count($newLogs),
            ];

        } catch (\Exception $e) {
            Log::error('Exception lors du polling des logs', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'console_id' => $consoleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'cached_logs' => Cache::get($cacheKey, []),
            ];
        }
    }

    /**
     * Normaliser les logs depuis différents formats de réponse CML
     */
    protected function normalizeLogs($response): array
    {
        if (is_array($response) && isset($response['log'])) {
            if (is_array($response['log'])) {
                return $response['log'];
            }
            if (is_string($response['log'])) {
                return explode("\n", $response['log']);
            }
        }

        if (is_string($response)) {
            return explode("\n", $response);
        }

        if (is_array($response)) {
            return $response;
        }

        return [];
    }

    /**
     * Détecter les nouvelles lignes par rapport au cache
     */
    protected function detectNewLogs(array $cachedLogs, array $newLogs): array
    {
        if (empty($cachedLogs)) {
            return $newLogs;
        }

        // Créer un set des lignes en cache pour une recherche rapide
        $cachedSet = array_flip(array_map('trim', $cachedLogs));

        // Filtrer les nouvelles lignes
        $newLines = [];
        foreach ($newLogs as $line) {
            $trimmedLine = trim($line);
            if (!empty($trimmedLine) && !isset($cachedSet[$trimmedLine])) {
                $newLines[] = $line;
            }
        }

        return $newLines;
    }

    /**
     * Parser les logs IOS pour détecter les prompts et structurer les résultats
     */
    protected function parseIOSLogs(array $logs): array
    {
        $parsed = [
            'prompts' => [],
            'commands' => [],
            'outputs' => [],
            'current_mode' => 'unknown',
            'hostname' => null,
        ];

        $currentCommand = null;
        $currentOutput = [];

        foreach ($logs as $index => $line) {
            $trimmedLine = trim($line);

            // Détecter les prompts IOS
            // Exemples: Router>, Router#, Router(config)#, Router(config-if)#
            if (preg_match('/^([A-Za-z0-9_-]+)(\(config[^)]*\))?([>#])/', $trimmedLine, $matches)) {
                $hostname = $matches[1];
                $configMode = $matches[2] ?? '';
                $privilege = $matches[3];

                $parsed['hostname'] = $hostname;
                
                // Déterminer le mode
                if ($privilege === '>') {
                    $parsed['current_mode'] = 'user';
                } elseif ($privilege === '#' && empty($configMode)) {
                    $parsed['current_mode'] = 'privileged';
                } elseif ($privilege === '#' && !empty($configMode)) {
                    $parsed['current_mode'] = 'config';
                }

                $parsed['prompts'][] = [
                    'line' => $trimmedLine,
                    'index' => $index,
                    'hostname' => $hostname,
                    'mode' => $parsed['current_mode'],
                ];

                // Si on a une commande en cours, sauvegarder son output
                if ($currentCommand !== null) {
                    $parsed['outputs'][] = [
                        'command' => $currentCommand,
                        'output' => $currentOutput,
                    ];
                    $currentOutput = [];
                }

                // Extraire la commande après le prompt
                $commandPart = substr($trimmedLine, strlen($matches[0]));
                if (!empty(trim($commandPart))) {
                    $currentCommand = trim($commandPart);
                    $parsed['commands'][] = [
                        'command' => $currentCommand,
                        'index' => $index,
                        'mode' => $parsed['current_mode'],
                    ];
                } else {
                    $currentCommand = null;
                }
            } else {
                // C'est une ligne d'output
                if ($currentCommand !== null) {
                    $currentOutput[] = $trimmedLine;
                }
            }
        }

        // Sauvegarder le dernier output si nécessaire
        if ($currentCommand !== null && !empty($currentOutput)) {
            $parsed['outputs'][] = [
                'command' => $currentCommand,
                'output' => $currentOutput,
            ];
        }

        return $parsed;
    }

    /**
     * Vider le cache pour une console
     */
    public function clearCache(string $labId, string $nodeId, string $consoleId): void
    {
        $cacheKey = "console_logs:{$labId}:{$nodeId}:{$consoleId}";
        Cache::forget($cacheKey);
    }

    /**
     * Configurer l'intervalle de polling
     */
    public function setPollInterval(int $milliseconds): void
    {
        $this->pollInterval = $milliseconds;
    }

    /**
     * Obtenir l'intervalle de polling recommandé
     */
    public function getRecommendedPollInterval(): int
    {
        return $this->pollInterval;
    }
}
