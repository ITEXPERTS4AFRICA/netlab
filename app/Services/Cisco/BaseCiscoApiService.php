<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Exceptions\CmlApiException;

abstract class BaseCiscoApiService
{
    protected $baseUrl;
    protected $cache;
    protected $resilience;
    protected $enableCache = true;
    protected $enableResilience = true;
    protected $throwExceptions = false; // Par défaut, retourner les erreurs dans un array

    public function __construct()
    {
        // Utiliser la configuration depuis la base de données (Setting) en priorité
        $this->baseUrl = $this->getCmlBaseUrl();
        $this->cache = app(CacheService::class);
        $this->resilience = app(ResilienceService::class);
    }

    /**
     * Obtenir le token depuis la session (source centralisée)
     * Le token est toujours récupéré depuis la session pour garantir la cohérence
     */
    protected function getToken(): ?string
    {
        return session('cml_token');
    }

    /**
     * Obtenir l'URL de base CML depuis la configuration
     * Priorité : Base de données (Setting) > config > .env
     */
    protected function getCmlBaseUrl(): ?string
    {
        return \App\Helpers\CmlConfigHelper::getBaseUrl();
    }

    /**
     * Obtenir les credentials CML depuis la configuration
     * Priorité : Base de données (Setting) > config > .env
     */
    protected function getCmlCredentials(): array
    {
        return \App\Helpers\CmlConfigHelper::getCredentials();
    }

    /**
     * Activer/désactiver le cache pour ce service
     */
    public function enableCache(bool $enable = true): self
    {
        $this->enableCache = $enable;
        return $this;
    }

    /**
     * Activer/désactiver le lancement d'exceptions pour les erreurs
     * Si activé, les erreurs lanceront CmlApiException au lieu de retourner un array avec 'error'
     */
    public function throwExceptions(bool $throw = true): self
    {
        $this->throwExceptions = $throw;
        return $this;
    }

    /**
     * GET avec cache automatique
     */
    protected function getCached(string $endpoint, string $cacheKey, ?int $ttl = null, array $headers = []): array
    {
        if (!$this->enableCache) {
            return $this->get($endpoint, $headers);
        }

        return $this->cache->remember(
            $cacheKey,
            $ttl ?? 300,
            fn() => $this->get($endpoint, $headers)
        );
    }

    /**
     * Effectuer une requête GET (avec resilience)
     */
    protected function get(string $endpoint, array $headers = []): array
    {
        if (!$this->enableResilience) {
            return $this->executeGet($endpoint, $headers);
        }

        return $this->resilience->withResilience(
            'cml-api',
            fn() => $this->executeGet($endpoint, $headers)
        );
    }

    /**
     * Exécuter GET sans resilience
     */
    private function executeGet(string $endpoint, array $headers = []): array
    {
        $fullUrl = "{$this->baseUrl}{$endpoint}";
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->get($fullUrl);

            return $this->handleResponse($response, "Unable to fetch from {$endpoint}", 'GET', $endpoint, $fullUrl);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorData = [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'GET',
            ];

            // Logger avec plus de contexte
            \Log::error('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'GET',
                'error' => $e->getMessage(),
                'service' => get_class($this),
                'trace' => $this->getCallTrace(),
            ]);

            // Notifier pour les erreurs de connexion
            $this->notifyCriticalError($endpoint, 503, $e->getMessage(), 'GET', $fullUrl);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Erreur de connexion au serveur CML: ' . $e->getMessage(),
                    $endpoint,
                    503,
                    [],
                    true,
                    $e
                );
            }

            return $errorData;
        } catch (CmlApiException $e) {
            // Ré-lancer les exceptions CML
            throw $e;
        } catch (\Exception $e) {
            $errorData = [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
            ];

            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service' => get_class($this),
            ]);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Exception lors de l\'appel CML: ' . $e->getMessage(),
                    $endpoint,
                    500,
                    [],
                    false,
                    $e
                );
            }

            return $errorData;
        }
    }

    /**
     * Effectuer une requête POST
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        $fullUrl = "{$this->baseUrl}{$endpoint}";
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->post($fullUrl, $data);

            return $this->handleResponse($response, "Unable to post to {$endpoint}", 'POST', $endpoint, $fullUrl);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorData = [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'POST',
            ];

            // Logger avec plus de contexte
            \Log::error('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'POST',
                'error' => $e->getMessage(),
                'service' => get_class($this),
                'trace' => $this->getCallTrace(),
            ]);

            // Notifier pour les erreurs de connexion
            $this->notifyCriticalError($endpoint, 503, $e->getMessage(), 'POST', $fullUrl);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Erreur de connexion au serveur CML: ' . $e->getMessage(),
                    $endpoint,
                    503,
                    [],
                    true,
                    $e
                );
            }

            return $errorData;
        } catch (CmlApiException $e) {
            // Ré-lancer les exceptions CML
            throw $e;
        } catch (\Exception $e) {
            $errorData = [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
            ];

            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service' => get_class($this),
            ]);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Exception lors de l\'appel CML: ' . $e->getMessage(),
                    $endpoint,
                    500,
                    [],
                    false,
                    $e
                );
            }

            return $errorData;
        }
    }

    /**
     * Effectuer une requête PATCH
     */
    protected function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        $fullUrl = "{$this->baseUrl}{$endpoint}";
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->patch($fullUrl, $data);

            return $this->handleResponse($response, "Unable to patch {$endpoint}", 'PATCH', $endpoint, $fullUrl);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorData = [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'PATCH',
            ];

            // Logger avec plus de contexte
            \Log::error('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'PATCH',
                'error' => $e->getMessage(),
                'service' => get_class($this),
                'trace' => $this->getCallTrace(),
            ]);

            // Notifier pour les erreurs de connexion
            $this->notifyCriticalError($endpoint, 503, $e->getMessage(), 'PATCH', $fullUrl);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Erreur de connexion au serveur CML: ' . $e->getMessage(),
                    $endpoint,
                    503,
                    [],
                    true,
                    $e
                );
            }

            return $errorData;
        } catch (CmlApiException $e) {
            // Ré-lancer les exceptions CML
            throw $e;
        } catch (\Exception $e) {
            $errorData = [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
            ];

            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service' => get_class($this),
            ]);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Exception lors de l\'appel CML: ' . $e->getMessage(),
                    $endpoint,
                    500,
                    [],
                    false,
                    $e
                );
            }

            return $errorData;
        }
    }

    /**
     * Effectuer une requête PUT
     */
    protected function put(string $endpoint, array $data = [], array $headers = []): array
    {
        $fullUrl = "{$this->baseUrl}{$endpoint}";
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->put($fullUrl, $data);

            return $this->handleResponse($response, "Unable to put to {$endpoint}", 'PUT', $endpoint, $fullUrl);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorData = [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'PUT',
            ];

            // Logger avec plus de contexte
            \Log::error('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'PUT',
                'error' => $e->getMessage(),
                'service' => get_class($this),
                'trace' => $this->getCallTrace(),
            ]);

            // Notifier pour les erreurs de connexion
            $this->notifyCriticalError($endpoint, 503, $e->getMessage(), 'PUT', $fullUrl);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Erreur de connexion au serveur CML: ' . $e->getMessage(),
                    $endpoint,
                    503,
                    [],
                    true,
                    $e
                );
            }

            return $errorData;
        } catch (CmlApiException $e) {
            // Ré-lancer les exceptions CML
            throw $e;
        } catch (\Exception $e) {
            $errorData = [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
            ];

            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service' => get_class($this),
            ]);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Exception lors de l\'appel CML: ' . $e->getMessage(),
                    $endpoint,
                    500,
                    [],
                    false,
                    $e
                );
            }

            return $errorData;
        }
    }

    /**
     * Effectuer une requête DELETE
     */
    protected function delete(string $endpoint, array $headers = []): array
    {
        $fullUrl = "{$this->baseUrl}{$endpoint}";
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->delete($fullUrl);

            return $this->handleResponse($response, "Unable to delete {$endpoint}", 'DELETE', $endpoint, $fullUrl);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorData = [
                'error' => 'Erreur de connexion au serveur CML. Le serveur est peut-être indisponible.',
                'status' => 503,
                'connection_error' => true,
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'DELETE',
            ];

            // Logger avec plus de contexte
            \Log::error('Erreur de connexion CML', [
                'endpoint' => $endpoint,
                'full_url' => $fullUrl,
                'method' => 'DELETE',
                'error' => $e->getMessage(),
                'service' => get_class($this),
                'trace' => $this->getCallTrace(),
            ]);

            // Notifier pour les erreurs de connexion
            $this->notifyCriticalError($endpoint, 503, $e->getMessage(), 'DELETE', $fullUrl);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Erreur de connexion au serveur CML: ' . $e->getMessage(),
                    $endpoint,
                    503,
                    [],
                    true,
                    $e
                );
            }

            return $errorData;
        } catch (CmlApiException $e) {
            // Ré-lancer les exceptions CML
            throw $e;
        } catch (\Exception $e) {
            $errorData = [
                'error' => 'Erreur lors de la communication avec CML: ' . $e->getMessage(),
                'status' => 500,
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
            ];

            \Log::error('Exception lors de l\'appel CML', [
                'endpoint' => $endpoint,
                'full_url' => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service' => get_class($this),
            ]);

            // Si les exceptions sont activées, lancer une exception
            if ($this->throwExceptions) {
                throw new CmlApiException(
                    'Exception lors de l\'appel CML: ' . $e->getMessage(),
                    $endpoint,
                    500,
                    [],
                    false,
                    $e
                );
            }

            return $errorData;
        }
    }

    /**
     * Gérer la réponse HTTP
     */
    protected function handleResponse(Response $response, string $errorMessage, string $method = 'GET', ?string $endpoint = null, ?string $fullUrl = null): array
    {
        if ($response->successful()) {
            $json = $response->json();
            // S'assurer qu'on retourne toujours un array
            return is_array($json) ? $json : ['data' => $json];
        }

        // Utiliser l'endpoint fourni ou l'extraire depuis le message d'erreur
        if (!$endpoint) {
            $endpoint = $this->extractEndpointFromErrorMessage($errorMessage);
        }
        if (!$fullUrl) {
            $fullUrl = $this->baseUrl . $endpoint;
        }

        // Essayer de parser le body comme JSON pour obtenir plus de détails
        $errorBody = $response->json();
        if (!is_array($errorBody)) {
            $errorBody = null;
        }
        $errorDetail = is_array($errorBody) && isset($errorBody['description'])
            ? $errorBody['description']
            : $response->body();

        // Préparer les données d'erreur avec toutes les informations
        $errorData = [
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $errorDetail,
            'detail' => $errorBody,
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'method' => $method,
            'service' => get_class($this),
            'call_trace' => $this->getCallTrace(),
        ];

        // Logger les détails de l'erreur avec plus de contexte
        $this->logApiError($endpoint, $response->status(), $errorDetail, $errorBody ?? [], $method, $fullUrl);

        // Si les exceptions sont activées, lancer une exception
        if ($this->throwExceptions) {
            throw new CmlApiException(
                "[{$method}] {$fullUrl}: {$errorDetail}",
                $endpoint,
                $response->status(),
                $errorBody,
                false
            );
        }

        return $errorData;
    }

    /**
     * Extraire l'endpoint depuis le message d'erreur
     */
    protected function extractEndpointFromErrorMessage(string $errorMessage): string
    {
        // Essayer d'extraire l'endpoint depuis le message
        if (preg_match('/Unable to (fetch|post|patch|put|delete) (?:from|to) (.+)/', $errorMessage, $matches)) {
            return $matches[2] ?? '';
        }
        return '';
    }

    /**
     * Obtenir la trace d'appel pour identifier l'origine de l'erreur
     */
    protected function getCallTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $relevantTrace = [];

        foreach ($trace as $index => $frame) {
            // Ignorer les frames internes de BaseCiscoApiService
            if (isset($frame['class']) && strpos($frame['class'], 'BaseCiscoApiService') !== false) {
                continue;
            }

            // Capturer les frames pertinents (appels depuis les services ou contrôleurs)
            if (isset($frame['file']) && isset($frame['line'])) {
                $relevantTrace[] = [
                    'file' => str_replace(base_path(), '', $frame['file']),
                    'line' => $frame['line'],
                    'function' => $frame['function'] ?? null,
                    'class' => $frame['class'] ?? null,
                ];

                // Limiter à 3 frames pertinents
                if (count($relevantTrace) >= 3) {
                    break;
                }
            }
        }

        return $relevantTrace;
    }

    /**
     * Logger les erreurs API avec plus de contexte
     */
    protected function logApiError(string $endpoint, int $status, string $errorDetail, ?array $errorBody = null, string $method = 'GET', ?string $fullUrl = null): void
    {
        $fullUrl = $fullUrl ?? ($this->baseUrl . $endpoint);

        $logContext = [
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'method' => $method,
            'status' => $status,
            'error_detail' => $errorDetail,
            'error_body' => $errorBody,
            'service' => get_class($this),
            'service_short' => class_basename($this),
            'timestamp' => now()->toIso8601String(),
            'call_trace' => $this->getCallTrace(),
        ];

        // Déterminer le niveau de log selon le statut HTTP
        if ($status >= 500) {
            // Erreurs serveur - niveau error
            \Log::error("Erreur API CML (5xx) - [{$method}] {$endpoint}", $logContext);

            // Notifier pour les erreurs critiques
            $this->notifyCriticalError($endpoint, $status, $errorDetail, $method, $fullUrl);
        } elseif ($status === 401 || $status === 403) {
            // Erreurs d'authentification - niveau warning
            \Log::warning("Erreur d'authentification API CML - [{$method}] {$endpoint}", $logContext);
        } elseif ($status === 404) {
            // Ressource non trouvée - niveau info
            \Log::info("Ressource CML non trouvée - [{$method}] {$endpoint}", $logContext);
        } else {
            // Autres erreurs client - niveau warning
            \Log::warning("Erreur API CML (4xx) - [{$method}] {$endpoint}", $logContext);
        }
    }

    /**
     * Notifier les erreurs critiques (5xx)
     */
    protected function notifyCriticalError(string $endpoint, int $status, string $errorDetail, string $method = 'GET', ?string $fullUrl = null): void
    {
        $fullUrl = $fullUrl ?? ($this->baseUrl . $endpoint);

        // Vous pouvez ajouter ici des notifications (email, Slack, etc.)
        // Pour l'instant, on log juste avec un tag spécial
        \Log::channel('single')->error('ERREUR CRITIQUE CML', [
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'method' => $method,
            'status' => $status,
            'error' => $errorDetail,
            'service' => class_basename($this),
            'call_trace' => $this->getCallTrace(),
            'action_required' => 'Vérifier la disponibilité du serveur CML',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Gérer la réponse pour un body brut (YAML, fichiers, etc.)
     */
    protected function handleRawResponse(Response $response, string $errorMessage)
    {
        if ($response->successful()) {
            return $response->body();
        }

        $endpoint = $this->extractEndpointFromErrorMessage($errorMessage);
        $fullUrl = $this->baseUrl . $endpoint;

        // Logger l'erreur
        $this->logApiError($endpoint, $response->status(), $response->body(), [], 'GET', $fullUrl);

        $errorData = [
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body(),
            'endpoint' => $endpoint,
        ];

        // Si les exceptions sont activées, lancer une exception
        if ($this->throwExceptions) {
            throw new CmlApiException(
                $errorMessage,
                $endpoint,
                $response->status(),
                ['body' => $response->body()],
                false
            );
        }

        return $errorData;
    }

    /**
     * Définir le token dans la session (source centralisée)
     * Tous les services récupèrent automatiquement le token depuis la session
     */
    public function setToken(string $token): void
    {
        session()->put('cml_token', $token);
    }

    /**
     * Définir l'URL de base
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Obtenir l'URL de base
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}

