import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

export type ConnectionQuality = 'excellent' | 'good' | 'poor' | 'disconnected';

interface UseConsoleStabilityOptions {
    maxRetries?: number;
    retryDelay?: number;
    onReconnect?: () => void;
    onError?: (error: Error) => void;
}

interface CommandQueueItem {
    command: string;
    timestamp: number;
    retries: number;
}

/**
 * Hook pour gérer la stabilité de la console avec reconnexion automatique,
 * queue de commandes et gestion d'erreurs robuste
 */
export const useConsoleStability = (options: UseConsoleStabilityOptions = {}) => {
    const {
        maxRetries = 3,
        retryDelay = 1000,
        onReconnect,
        onError,
    } = options;

    const [isConnected, setIsConnected] = useState(false);
    const [connectionQuality, setConnectionQuality] = useState<ConnectionQuality>('disconnected');
    const [latency, setLatency] = useState<number | null>(null);
    const [commandQueue, setCommandQueue] = useState<CommandQueueItem[]>([]);
    const [retryCount, setRetryCount] = useState(0);
    
    const retryTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const latencyCheckIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastPingTimeRef = useRef<number | null>(null);
    const isReconnectingRef = useRef(false);

    /**
     * Mesure la latence de la connexion
     */
    const measureLatency = useCallback(async (): Promise<number | null> => {
        if (!isConnected) return null;

        try {
            const startTime = performance.now();
            lastPingTimeRef.current = startTime;
            
            // Simuler un ping (à adapter selon votre API)
            const response = await fetch('/api/console/ping', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            if (response.ok) {
                const endTime = performance.now();
                const measuredLatency = endTime - startTime;
                setLatency(measuredLatency);
                
                // Déterminer la qualité de connexion
                if (measuredLatency < 100) {
                    setConnectionQuality('excellent');
                } else if (measuredLatency < 300) {
                    setConnectionQuality('good');
                } else {
                    setConnectionQuality('poor');
                }
                
                return measuredLatency;
            }
        } catch (error) {
            console.error('Erreur de mesure de latence:', error);
            setConnectionQuality('poor');
        }
        
        return null;
    }, [isConnected]);

    /**
     * Démarre le monitoring de latence
     */
    useEffect(() => {
        if (!isConnected) {
            if (latencyCheckIntervalRef.current) {
                clearInterval(latencyCheckIntervalRef.current);
                latencyCheckIntervalRef.current = null;
            }
            return;
        }

        // Mesurer la latence immédiatement
        measureLatency();

        // Puis toutes les 5 secondes
        latencyCheckIntervalRef.current = setInterval(() => {
            measureLatency();
        }, 5000);

        return () => {
            if (latencyCheckIntervalRef.current) {
                clearInterval(latencyCheckIntervalRef.current);
            }
        };
    }, [isConnected, measureLatency]);

    /**
     * Ajoute une commande à la queue
     */
    const queueCommand = useCallback((command: string) => {
        const item: CommandQueueItem = {
            command,
            timestamp: Date.now(),
            retries: 0,
        };
        
        setCommandQueue(prev => [...prev, item]);
    }, []);

    /**
     * Traite la queue de commandes
     */
    const processQueue = useCallback(async (sendCommand: (cmd: string) => Promise<void>) => {
        if (commandQueue.length === 0 || !isConnected) return;

        const [firstItem, ...rest] = commandQueue;
        
        try {
            await sendCommand(firstItem.command);
            // Commande envoyée avec succès, la retirer de la queue
            setCommandQueue(rest);
            setRetryCount(0);
        } catch (error) {
            // Erreur lors de l'envoi
            if (firstItem.retries < maxRetries) {
                // Réessayer
                const updatedItem = { ...firstItem, retries: firstItem.retries + 1 };
                setCommandQueue([updatedItem, ...rest]);
                setRetryCount(prev => prev + 1);
                
                // Attendre avant de réessayer
                await new Promise(resolve => setTimeout(resolve, retryDelay * (updatedItem.retries + 1)));
                await processQueue(sendCommand);
            } else {
                // Trop de tentatives, retirer de la queue
                setCommandQueue(rest);
                const err = error instanceof Error ? error : new Error('Échec d\'envoi de commande');
                onError?.(err);
                toast.error(`Impossible d'envoyer la commande: ${firstItem.command}`);
            }
        }
    }, [commandQueue, isConnected, maxRetries, retryDelay, onError]);

    /**
     * Tente une reconnexion
     */
    const attemptReconnect = useCallback(async (reconnectFn: () => Promise<void>) => {
        if (isReconnectingRef.current) return;
        
        isReconnectingRef.current = true;
        setRetryCount(0);

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                await reconnectFn();
                setIsConnected(true);
                setConnectionQuality('good');
                isReconnectingRef.current = false;
                onReconnect?.();
                toast.success('Reconnexion réussie');
                return;
            } catch (error) {
                if (attempt < maxRetries) {
                    const delay = retryDelay * Math.pow(2, attempt - 1); // Backoff exponentiel
                    await new Promise(resolve => setTimeout(resolve, delay));
                } else {
                    isReconnectingRef.current = false;
                    setConnectionQuality('disconnected');
                    const err = error instanceof Error ? error : new Error('Échec de reconnexion');
                    onError?.(err);
                    toast.error('Impossible de se reconnecter');
                }
            }
        }
    }, [maxRetries, retryDelay, onReconnect, onError]);

    /**
     * Gère les erreurs avec retry automatique
     */
    const handleError = useCallback((error: Error, retryFn?: () => Promise<void>) => {
        console.error('Erreur console:', error);
        onError?.(error);

        if (retryFn && !isReconnectingRef.current) {
            setIsConnected(false);
            setConnectionQuality('disconnected');
            attemptReconnect(retryFn);
        }
    }, [onError, attemptReconnect]);

    /**
     * Nettoie les ressources
     */
    useEffect(() => {
        return () => {
            if (retryTimeoutRef.current) {
                clearTimeout(retryTimeoutRef.current);
            }
            if (latencyCheckIntervalRef.current) {
                clearInterval(latencyCheckIntervalRef.current);
            }
        };
    }, []);

    return {
        isConnected,
        setIsConnected,
        connectionQuality,
        latency,
        commandQueue,
        retryCount,
        queueCommand,
        processQueue,
        attemptReconnect,
        handleError,
        measureLatency,
    };
};



