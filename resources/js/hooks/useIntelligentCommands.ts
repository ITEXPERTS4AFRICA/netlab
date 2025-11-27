import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export type GeneratedCommand = {
    command: string;
    description: string;
    category: string;
    priority: number;
};

export type NodeCommands = {
    node_id: string;
    node_label: string;
    node_definition: string;
    commands: GeneratedCommand[];
};

export type LabAnalysis = {
    lab_id: string;
    total_nodes: number;
    total_commands: number;
    commands_by_node: Record<string, NodeCommands>;
    error?: string;
};

export const useIntelligentCommands = () => {
    const [analysis, setAnalysis] = useState<LabAnalysis | null>(null);
    const [recommendedCommands, setRecommendedCommands] = useState<GeneratedCommand[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);


    /**
     * Analyser le lab et générer des commandes intelligentes
     */
    const analyzeLab = useCallback(async (labId: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/api/labs/${labId}/commands/analyze`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data: LabAnalysis = await response.json();
            setAnalysis(data);
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'analyse du lab';
            setError(errorMessage);
            toast.error(errorMessage);
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Obtenir les commandes recommandées pour un node spécifique
     */
    const getRecommendedCommands = useCallback(async (labId: string, nodeId: string) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/commands/recommended`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            setRecommendedCommands(data.commands || []);
            return data.commands || [];
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des commandes';
            setError(errorMessage);
            toast.error(errorMessage);
            return [];
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Générer un script de configuration automatique
     */
    const generateScript = useCallback(async (labId: string, options?: {
        include_config?: boolean;
        include_show?: boolean;
        format?: 'script' | 'json';
    }) => {
        setLoading(true);
        setError(null);
        try {
            const queryParams = new URLSearchParams();
            if (options?.include_config !== undefined) {
                queryParams.append('include_config', options.include_config.toString());
            }
            if (options?.include_show !== undefined) {
                queryParams.append('include_show', options.include_show.toString());
            }
            if (options?.format) {
                queryParams.append('format', options.format);
            }

            const url = `/api/labs/${labId}/commands/script${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
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
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la génération du script';
            setError(errorMessage);
            toast.error(errorMessage);
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Exécuter une commande générée (retourne les instructions car CML n'a pas d'API directe)
     */
    const executeCommand = useCallback(async (
        labId: string,
        nodeId: string,
        command: string,
        category?: string
    ) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/commands/execute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    command,
                    category: category || 'general',
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            // Note: CML n'a pas d'API pour exécuter directement, donc on retourne les instructions
            toast.info('Commande prête à être exécutée. Tapez-la dans la console.');
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'exécution de la commande';
            setError(errorMessage);
            toast.error(errorMessage);
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        analysis,
        recommendedCommands,
        loading,
        error,
        analyzeLab,
        getRecommendedCommands,
        generateScript,
        executeCommand,
    };
};

