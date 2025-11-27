import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export const useNodeConfig = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    /**
     * Récupérer la configuration d'un node
     */
    const getNodeConfig = useCallback(async (
        labId: string,
        nodeId: string
    ): Promise<string | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/config`, {
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
            
            // Normaliser la configuration (peut être string ou array de fichiers)
            let config = '';
            if (typeof data.configuration === 'string') {
                config = data.configuration;
            } else if (Array.isArray(data.configuration)) {
                config = data.configuration.map((file: any) => {
                    if (file.name && file.content) {
                        return `! File: ${file.name}\n${file.content}`;
                    }
                    return file.content || '';
                }).join('\n\n');
            }

            return config;
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
     * Uploader une configuration pour un node
     */
    const uploadNodeConfig = useCallback(async (
        labId: string,
        nodeId: string,
        configuration: string,
        fileName?: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/config/upload`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    configuration,
                    name: fileName,
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            toast.success(data.message || 'Configuration uploadée avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'upload de la configuration';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Extraire la configuration d'un node (depuis le device)
     */
    const extractNodeConfig = useCallback(async (
        labId: string,
        nodeId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/config/extract`, {
                method: 'PUT',
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
            toast.success(data.message || 'Configuration extraite avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'extraction de la configuration';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Exporter la configuration d'un node (téléchargement)
     */
    const exportNodeConfig = useCallback(async (
        labId: string,
        nodeId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/config/export`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.text();
                throw new Error(`Erreur ${response.status}: ${errorData}`);
            }

            // Créer un blob et télécharger
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            // Récupérer le nom de fichier depuis les headers
            const contentDisposition = response.headers.get('Content-Disposition');
            const filename = contentDisposition
                ? contentDisposition.split('filename=')[1]?.replace(/"/g, '') || `node_${nodeId}_config.txt`
                : `node_${nodeId}_config.txt`;
            
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            toast.success('Configuration exportée avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'export de la configuration';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Exporter le lab complet (YAML)
     */
    const exportLab = useCallback(async (labId: string): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/export`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.text();
                throw new Error(`Erreur ${response.status}: ${errorData}`);
            }

            // Créer un blob et télécharger
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            // Récupérer le nom de fichier depuis les headers
            const contentDisposition = response.headers.get('Content-Disposition');
            const filename = contentDisposition
                ? contentDisposition.split('filename=')[1]?.replace(/"/g, '') || `lab_${labId}_export.yaml`
                : `lab_${labId}_export.yaml`;
            
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            toast.success('Lab exporté avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de l\'export du lab';
            setError(errorMessage);
            toast.error(`❌ ${errorMessage}`);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        loading,
        error,
        getNodeConfig,
        uploadNodeConfig,
        extractNodeConfig,
        exportNodeConfig,
        exportLab,
    };
};


