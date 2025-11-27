import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export interface LabDetails {
    lab: any;
    topology: any;
    state: any;
    events: any[];
    nodes: any[];
    links: any[];
    interfaces: any[];
    annotations: any[];
    simulation_stats?: any;
    layer3_addresses?: any;
}

export const useLabDetails = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [details, setDetails] = useState<LabDetails | null>(null);

    /**
     * Récupérer tous les détails d'un lab
     */
    const getLabDetails = useCallback(async (labId: string): Promise<LabDetails | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/details`, {
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

            const data: LabDetails = await response.json();
            setDetails(data);
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des détails';
            setError(errorMessage);
            
            // Ne pas afficher de toast pour les erreurs 404 (endpoint peut ne pas exister)
            if (!errorMessage.includes('404')) {
                toast.error(`❌ ${errorMessage}`);
            }
            console.error('Erreur getLabDetails:', err);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupérer les statistiques de simulation
     */
    const getSimulationStats = useCallback(async (labId: string): Promise<any | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/simulation-stats`, {
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
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des stats';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupérer les adresses Layer 3
     */
    const getLayer3Addresses = useCallback(async (labId: string): Promise<any | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/layer3-addresses`, {
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
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des adresses';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        loading,
        error,
        details,
        getLabDetails,
        getSimulationStats,
        getLayer3Addresses,
    };
};

