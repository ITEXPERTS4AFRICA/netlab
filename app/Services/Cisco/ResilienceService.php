<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Cache;
use Exception;

class ResilienceService
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // millisecondes
    protected int $circuitBreakerThreshold = 5;
    protected int $circuitBreakerTimeout = 60; // secondes
    protected string $cachePrefix = 'circuit:';

    /**
     * Exécuter avec retry automatique
     */
    public function withRetry(callable $callback, int $maxRetries = null, int $delay = null)
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $delay = $delay ?? $this->retryDelay;
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                return $callback();
            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw $e;
                }

                // Backoff exponentiel
                $waitTime = $delay * pow(2, $attempts - 1);
                usleep($waitTime * 1000);
            }
        }

        throw $lastException;
    }

    /**
     * Exécuter avec circuit breaker
     */
    public function withCircuitBreaker(string $service, callable $callback)
    {
        $key = $this->cachePrefix . $service;

        // Vérifier si le circuit est ouvert
        if ($this->isCircuitOpen($service)) {
            // Vérifier si on peut tenter de le refermer
            if ($this->shouldAttemptReset($service)) {
                // Mode half-open : on tente une requête
                try {
                    $result = $callback();
                    $this->recordSuccess($service);
                    return $result;
                } catch (Exception $e) {
                    $this->recordFailure($service);
                    throw new \RuntimeException("Circuit breaker open for {$service}. Last error: " . $e->getMessage());
                }
            }

            throw new \RuntimeException("Circuit breaker open for {$service}");
        }

        // Circuit fermé : exécution normale
        try {
            $result = $callback();
            $this->recordSuccess($service);
            return $result;
        } catch (Exception $e) {
            $this->recordFailure($service);
            throw $e;
        }
    }

    /**
     * Exécuter avec retry ET circuit breaker
     */
    public function withResilience(string $service, callable $callback, int $maxRetries = null)
    {
        return $this->withCircuitBreaker($service, function() use ($callback, $maxRetries) {
            return $this->withRetry($callback, $maxRetries);
        });
    }

    /**
     * Vérifier si le circuit est ouvert
     */
    protected function isCircuitOpen(string $service): bool
    {
        $failures = Cache::get($this->cachePrefix . $service . ':failures', 0);
        return $failures >= $this->circuitBreakerThreshold;
    }

    /**
     * Vérifier si on devrait tenter de refermer le circuit
     */
    protected function shouldAttemptReset(string $service): bool
    {
        $lastAttempt = Cache::get($this->cachePrefix . $service . ':last_attempt');
        
        if (!$lastAttempt) {
            return true;
        }

        return time() - $lastAttempt >= $this->circuitBreakerTimeout;
    }

    /**
     * Enregistrer un succès
     */
    protected function recordSuccess(string $service): void
    {
        Cache::forget($this->cachePrefix . $service . ':failures');
        Cache::forget($this->cachePrefix . $service . ':last_attempt');
    }

    /**
     * Enregistrer un échec
     */
    protected function recordFailure(string $service): void
    {
        $key = $this->cachePrefix . $service . ':failures';
        $failures = Cache::get($key, 0);
        Cache::put($key, $failures + 1, 300); // 5 minutes TTL
        Cache::put($this->cachePrefix . $service . ':last_attempt', time(), 300);
    }

    /**
     * Réinitialiser le circuit breaker
     */
    public function resetCircuitBreaker(string $service): void
    {
        Cache::forget($this->cachePrefix . $service . ':failures');
        Cache::forget($this->cachePrefix . $service . ':last_attempt');
    }

    /**
     * Obtenir l'état du circuit
     */
    public function getCircuitStatus(string $service): array
    {
        $failures = Cache::get($this->cachePrefix . $service . ':failures', 0);
        $lastAttempt = Cache::get($this->cachePrefix . $service . ':last_attempt');
        
        return [
            'service' => $service,
            'failures' => $failures,
            'is_open' => $this->isCircuitOpen($service),
            'threshold' => $this->circuitBreakerThreshold,
            'last_attempt' => $lastAttempt,
            'can_retry' => $this->shouldAttemptReset($service),
        ];
    }

    /**
     * Configurer les paramètres
     */
    public function configure(array $config): self
    {
        if (isset($config['max_retries'])) {
            $this->maxRetries = $config['max_retries'];
        }
        
        if (isset($config['retry_delay'])) {
            $this->retryDelay = $config['retry_delay'];
        }
        
        if (isset($config['circuit_threshold'])) {
            $this->circuitBreakerThreshold = $config['circuit_threshold'];
        }
        
        if (isset($config['circuit_timeout'])) {
            $this->circuitBreakerTimeout = $config['circuit_timeout'];
        }

        return $this;
    }
}

