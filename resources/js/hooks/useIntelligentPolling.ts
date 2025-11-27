import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

type PollingConfig = {
    labId: string;
    nodeId: string;
    consoleId: string;
    enabled: boolean;
    interval?: number; // ms
};

type ParsedLog = {
    prompts: Array<{
        line: string;
        index: number;
        hostname: string;
        mode: string;
    }>;
    commands: Array<{
        command: string;
        index: number;
        mode: string;
    }>;
    outputs: Array<{
        command: string;
        output: string[];
    }>;
    current_mode: string;
    hostname: string | null;
};

type PollingResult = {
    success: boolean;
    logs: string[];
    new_logs: string[];
    parsed: ParsedLog;
    total_lines: number;
    new_lines: number;
    error?: string;
    rate_limited?: boolean;
    cached_logs?: string[]; // Logs en cache en cas d'erreur
};

/**
 * Hook pour le polling intelligent des logs console
 * 
 * Fonctionnalités :
 * - Polling automatique avec intervalle configurable
 * - Cache côté serveur pour éviter les doublons
 * - Parsing automatique des prompts IOS
 * - Détection du mode IOS (user, privileged, config)
 * - Gestion du rate limiting
 */
export function useIntelligentPolling(config: PollingConfig) {
    const [logs, setLogs] = useState<string[]>([]);
    const [parsedLogs, setParsedLogs] = useState<ParsedLog | null>(null);
    const [isPolling, setIsPolling] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [lastPollTime, setLastPollTime] = useState<Date | null>(null);

    const intervalRef = useRef<NodeJS.Timeout | null>(null);
    const abortControllerRef = useRef<AbortController | null>(null);
    const rateLimitCountRef = useRef<number>(0);
    const currentIntervalRef = useRef<number>(config.interval || 2000);

    const pollLogs = useCallback(async () => {
        if (!config.enabled || !config.consoleId) {
            return;
        }

        // Annuler la requête précédente si elle est toujours en cours
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }

        abortControllerRef.current = new AbortController();

        try {
            setIsPolling(true);
            setError(null);

            const response = await fetch(
                `/api/labs/${config.labId}/nodes/${config.nodeId}/consoles/${config.consoleId}/poll`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: abortControllerRef.current.signal,
                }
            );

            if (!response.ok) {
                if (response.status === 429) {
                    // Rate limited - augmenter l'intervalle progressivement
                    rateLimitCountRef.current++;
                    const data = await response.json().catch(() => ({}));
                    
                    // Augmenter l'intervalle de polling (backoff exponentiel)
                    const newInterval = Math.min(
                        currentIntervalRef.current * 2,
                        10000 // Maximum 10 secondes
                    );
                    currentIntervalRef.current = newInterval;
                    
                    setError(`Rate limit atteint (${rateLimitCountRef.current}x). Polling ralenti à ${newInterval}ms...`);

                    // Utiliser les logs en cache si disponibles
                    if (data.cached_logs && Array.isArray(data.cached_logs)) {
                        setLogs(data.cached_logs);
                    }

                    // Réinitialiser l'intervalle si on a trop d'erreurs consécutives
                    if (rateLimitCountRef.current >= 3) {
                        console.warn('⚠️ Trop d\'erreurs 429, arrêt temporaire du polling');
                        // Arrêter le polling temporairement
                        if (intervalRef.current) {
                            clearInterval(intervalRef.current);
                            intervalRef.current = null;
                        }
                        // Redémarrer après 30 secondes
                        setTimeout(() => {
                            rateLimitCountRef.current = 0;
                            currentIntervalRef.current = config.interval || 2000;
                            if (config.enabled) {
                                pollLogs();
                                intervalRef.current = setInterval(pollLogs, currentIntervalRef.current);
                            }
                        }, 30000);
                    } else {
                        // Réinitialiser l'intervalle avec le nouveau délai
                        if (intervalRef.current) {
                            clearInterval(intervalRef.current);
                        }
                        intervalRef.current = setInterval(pollLogs, currentIntervalRef.current);
                    }

                    return;
                }

                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Réinitialiser le compteur d'erreurs en cas de succès
            if (rateLimitCountRef.current > 0) {
                rateLimitCountRef.current = 0;
                currentIntervalRef.current = config.interval || 3000;
                // Réinitialiser l'intervalle si nécessaire
                if (intervalRef.current) {
                    clearInterval(intervalRef.current);
                }
                intervalRef.current = setInterval(pollLogs, currentIntervalRef.current);
            }

            const data: PollingResult = await response.json();

            if (data.error) {
                setError(data.error);

                // Utiliser les logs en cache si disponibles
                if (data.cached_logs && Array.isArray(data.cached_logs)) {
                    setLogs(data.cached_logs);
                }

                return;
            }

            if (data.success) {
                // Mettre à jour les logs
                if (data.logs && Array.isArray(data.logs)) {
                    setLogs(data.logs);
                }

                // Mettre à jour les logs parsés
                if (data.parsed) {
                    setParsedLogs(data.parsed);
                }

                setLastPollTime(new Date());

                // Afficher une notification si de nouvelles lignes sont détectées
                if (data.new_lines > 0) {
                    console.log(`[Polling] ${data.new_lines} nouvelle(s) ligne(s) détectée(s)`);
                }
            }

        } catch (err) {
            if (err instanceof Error) {
                if (err.name === 'AbortError') {
                    // Requête annulée, pas d'erreur
                    return;
                }

                console.error('Erreur lors du polling:', err);
                setError(err.message);
            }
        } finally {
            setIsPolling(false);
        }
    }, [config.labId, config.nodeId, config.consoleId, config.enabled]);

    // Démarrer/arrêter le polling
    useEffect(() => {
        if (!config.enabled) {
            // Arrêter le polling
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
            return;
        }

        // Polling initial avec délai pour éviter les requêtes simultanées
        const initialDelay = Math.random() * 1000; // Délai aléatoire entre 0 et 1 seconde
        setTimeout(() => {
            pollLogs();
        }, initialDelay);

        // Configurer le polling périodique avec intervalle minimum de 3 secondes
        const interval = Math.max(config.interval || 3000, 3000); // Minimum 3 secondes
        currentIntervalRef.current = interval;
        intervalRef.current = setInterval(pollLogs, interval);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }

            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
        };
    }, [config.enabled, config.interval, pollLogs]);

    // Fonction pour vider le cache
    const clearCache = useCallback(async () => {
        try {
            const response = await fetch(
                `/api/labs/${config.labId}/nodes/${config.nodeId}/consoles/${config.consoleId}/cache`,
                {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            setLogs([]);
            setParsedLogs(null);
            toast.success('Cache vidé avec succès');

            // Relancer le polling immédiatement
            await pollLogs();

        } catch (err) {
            console.error('Erreur lors du vidage du cache:', err);
            toast.error('Impossible de vider le cache');
        }
    }, [config.labId, config.nodeId, config.consoleId, pollLogs]);

    // Fonction pour forcer un poll immédiat
    const forcePoll = useCallback(() => {
        return pollLogs();
    }, [pollLogs]);

    return {
        logs,
        parsedLogs,
        isPolling,
        error,
        lastPollTime,
        clearCache,
        forcePoll,

        // Informations dérivées
        currentMode: parsedLogs?.current_mode || 'unknown',
        hostname: parsedLogs?.hostname || null,
        totalLines: logs.length,
    };
}
