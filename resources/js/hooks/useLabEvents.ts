import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export interface LabEvent {
    id?: string;
    type?: string;
    timestamp?: string;
    node_id?: string;
    interface_id?: string;
    link_id?: string;
    message?: string;
    details?: any;
    [key: string]: any;
}

export const useLabEvents = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [events, setEvents] = useState<LabEvent[]>([]);

    /**
     * Récupérer les événements d'un lab
     */
    const getLabEvents = useCallback(async (
        labId: string,
        options?: { type?: string; limit?: number }
    ): Promise<LabEvent[]> => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams();
            if (options?.type) params.append('type', options.type);
            if (options?.limit) params.append('limit', options.limit.toString());

            const url = `/api/labs/${labId}/events${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            const eventsList = data.events || [];
            setEvents(eventsList);
            return eventsList;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des événements';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            console.error('Erreur getLabEvents:', err);
            return [];
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupérer les événements d'un node
     */
    const getNodeEvents = useCallback(async (
        labId: string,
        nodeId: string,
        limit?: number
    ): Promise<LabEvent[]> => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams();
            if (limit) params.append('limit', limit.toString());

            const url = `/api/labs/${labId}/nodes/${nodeId}/events${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            const eventsList = data.events || [];
            return eventsList;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des événements';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return [];
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupérer les événements d'une interface
     */
    const getInterfaceEvents = useCallback(async (
        labId: string,
        interfaceId: string,
        limit?: number
    ): Promise<LabEvent[]> => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams();
            if (limit) params.append('limit', limit.toString());

            const url = `/api/labs/${labId}/interfaces/${interfaceId}/events${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            const eventsList = data.events || [];
            return eventsList;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des événements';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return [];
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        loading,
        error,
        events,
        getLabEvents,
        getNodeEvents,
        getInterfaceEvents,
    };
};


