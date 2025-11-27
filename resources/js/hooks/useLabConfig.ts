import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export interface LabConfig {
    lab_id: string;
    lab: any;
    topology: any;
    yaml: string;
}

export const useLabConfig = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [config, setConfig] = useState<LabConfig | null>(null);

    /**
     * Récupérer la configuration complète du lab
     */
    const getLabConfig = useCallback(async (labId: string): Promise<LabConfig | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/config`, {
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

            const data: LabConfig = await response.json();
            setConfig(data);
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération de la configuration';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Mettre à jour la configuration complète du lab
     */
    const updateLabConfig = useCallback(async (
        labId: string,
        topology?: any,
        yaml?: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const body: any = {};
            if (topology) body.topology = topology;
            if (yaml) body.yaml = yaml;

            const response = await fetch(`/api/labs/${labId}/config`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            toast.success(data.message || 'Configuration mise à jour avec succès');
            
            // Recharger la config après mise à jour
            await getLabConfig(labId);
            
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la mise à jour de la configuration';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return false;
        } finally {
            setLoading(false);
        }
    }, [getLabConfig]);

    return {
        loading,
        error,
        config,
        getLabConfig,
        updateLabConfig,
    };
};


