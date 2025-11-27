import { useState, useEffect, useCallback, useRef } from 'react';
import { toast } from 'sonner';

export interface LogEntry {
    id: string;
    timestamp: string;
    level: 'info' | 'warning' | 'error' | 'success';
    message: string;
    source?: string;
    details?: any;
}

export const useRealtimeLogs = (labId: string, enabled: boolean = true, interval: number = 2000) => {
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastEventIdRef = useRef<string | null>(null);

    /**
     * Récupérer les nouveaux événements depuis la dernière récupération
     */
    const fetchNewEvents = useCallback(async () => {
        if (!enabled || !labId) return;

        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (lastEventIdRef.current) {
                params.append('after', lastEventIdRef.current);
            }
            params.append('limit', '50');
            
            // Note: Le backend filtre déjà les événements, pas besoin de paramètre 'after' si non supporté

            const response = await fetch(`/api/labs/${labId}/events?${params.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}`);
            }

            const data = await response.json();
            const events = data.events || [];

            if (events.length > 0) {
                // Convertir les événements en logs
                const newLogs: LogEntry[] = events.map((event: any, index: number) => {
                    // Générer un ID unique en combinant timestamp, index et un identifiant aléatoire
                    const timestamp = Date.now();
                    const randomId = Math.random().toString(36).substr(2, 9);
                    const eventId = event.id || `${timestamp}-${index}-${randomId}`;
                    if (!lastEventIdRef.current || eventId > lastEventIdRef.current) {
                        lastEventIdRef.current = eventId;
                    }

                    return {
                        id: eventId,
                        timestamp: event.timestamp || new Date().toISOString(),
                        level: event.type?.includes('error') ? 'error' :
                               event.type?.includes('warning') ? 'warning' :
                               event.type?.includes('success') ? 'success' : 'info',
                        message: event.message || event.type || 'Événement inconnu',
                        source: event.node_id || event.interface_id || event.link_id || 'lab',
                        details: event,
                    };
                });

                setLogs((prev) => {
                    const combined = [...prev, ...newLogs];
                    // Garder seulement les 100 derniers logs
                    return combined.slice(-100);
                });

                // Notifier les nouveaux logs importants
                newLogs.forEach((log) => {
                    if (log.level === 'error') {
                        toast.error(`❌ ${log.message}`, {
                            description: log.source ? `Source: ${log.source}` : undefined,
                        });
                    } else if (log.level === 'warning') {
                        toast.warning(`⚠️ ${log.message}`, {
                            description: log.source ? `Source: ${log.source}` : undefined,
                        });
                    }
                });
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des logs';
            setError(errorMessage);
            console.error('Erreur fetchNewEvents:', err);
        } finally {
            setLoading(false);
        }
    }, [enabled, labId]);

    /**
     * Démarrer le polling en temps réel
     */
    useEffect(() => {
        if (!enabled || !labId) {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
            return;
        }

        // Récupération initiale
        void fetchNewEvents();

        // Polling périodique
        intervalRef.current = setInterval(() => {
            void fetchNewEvents();
        }, interval);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };
    }, [enabled, labId, interval, fetchNewEvents]);

    /**
     * Ajouter un log manuellement
     */
    const addLog = useCallback((log: Omit<LogEntry, 'id' | 'timestamp'>) => {
        // Générer un ID unique avec timestamp, random et compteur pour éviter les doublons
        const timestamp = Date.now();
        const randomId = Math.random().toString(36).substr(2, 9);
        const counter = Math.floor(Math.random() * 10000);
        const uniqueId = `${timestamp}-${randomId}-${counter}`;
        
        const newLog: LogEntry = {
            ...log,
            id: uniqueId,
            timestamp: new Date().toISOString(),
        };
        setLogs((prev) => [...prev, newLog].slice(-100));
    }, []);

    /**
     * Effacer les logs
     */
    const clearLogs = useCallback(() => {
        setLogs([]);
        lastEventIdRef.current = null;
    }, []);

    return {
        logs,
        loading,
        error,
        addLog,
        clearLogs,
        refresh: fetchNewEvents,
    };
};

