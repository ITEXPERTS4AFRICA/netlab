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
                    // Rate limited
                    const data = await response.json();
                    setError('Rate limit atteint. Ralentissement du polling...');

                    // Utiliser les logs en cache si disponibles
                    if (data.cached_logs && Array.isArray(data.cached_logs)) {
                        setLogs(data.cached_logs);
                    }

                    return;
                }

                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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

        // Polling initial
        pollLogs();

        // Configurer le polling périodique
        const interval = config.interval || 2000; // 2 secondes par défaut
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
