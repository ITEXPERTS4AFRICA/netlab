/**
 * Hook personnalisé pour gérer les appels API console avec types TypeScript
 * Utilise les types générés depuis l'OpenAPI CML
 */

import { useState, useCallback } from 'react';
import { toast } from 'sonner';
import type { components } from '@/types/cml-api';

// Types basés sur les réponses API réelles
type ConsoleResponse = {
    consoles: Array<{
        id: string;
        console_type?: string;
        [key: string]: unknown;
    }>;
    available_types?: {
        console?: boolean;
        serial?: boolean;
        vnc?: boolean;
        [key: string]: boolean | undefined;
    };
};

type ConsoleSessionResponse = {
    session_id?: string;
    id?: string;
    lab_id?: string;
    node_id?: string;
    type?: string;
    protocol?: string;
    ws_href?: string;
    ws?: string;
    ws_url?: string;
    [key: string]: unknown;
};

type ConsoleSessionsListResponse = {
    sessions?: Array<{
        session_id?: string;
        lab_id?: string;
        node_id?: string;
        type?: string;
        [key: string]: unknown;
    }>;
    [key: string]: unknown;
};

type ConsoleLogResponse = {
    log?: string[] | string;
    [key: string]: unknown;
};

type CreateSessionPayload = {
    lab_id: string;
    node_id: string;
    type?: string;
    protocol?: string;
    options?: Record<string, unknown>;
};

const getCsrfToken = (): string | null => {
    const element = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return element?.content || null;
};

export const useConsole = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    /**
     * Récupère les consoles disponibles pour un nœud
     */
    const getNodeConsoles = useCallback(async (
        labId: string,
        nodeId: string
    ): Promise<ConsoleResponse | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/consoles`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data: ConsoleResponse = await response.json();
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des consoles';
            setError(errorMessage);
            toast.error(errorMessage);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Crée une session console pour un nœud
     */
    const createSession = useCallback(async (
        payload: CreateSessionPayload
    ): Promise<ConsoleSessionResponse | null> => {
        setLoading(true);
        setError(null);

        const csrf = getCsrfToken();
        if (!csrf) {
            const errorMsg = 'Jeton CSRF introuvable';
            setError(errorMsg);
            toast.error(errorMsg);
            return null;
        }

        try {
            const response = await fetch('/api/console/sessions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                // Extraire le message d'erreur de manière plus intelligente
                let errorMessage = errorData?.error ?? errorData?.message ?? `Erreur ${response.status}`;
                
                // Si c'est un objet avec une description, l'utiliser
                if (typeof errorData?.body === 'string') {
                    errorMessage = errorData.body;
                } else if (errorData?.detail?.description) {
                    errorMessage = errorData.detail.description;
                } else if (errorData?.body?.description) {
                    errorMessage = errorData.body.description;
                }
                
                console.error('Erreur création session:', {
                    status: response.status,
                    error: errorMessage,
                    fullBody: errorData,
                });
                
                throw new Error(errorMessage);
            }

            const data: ConsoleSessionResponse = await response.json();
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la création de la session';
            setError(errorMessage);
            
            // Message d'erreur plus spécifique selon le type d'erreur
            let toastMessage = errorMessage;
            if (errorMessage.includes('Unable to post')) {
                toastMessage = 'Impossible de se connecter au serveur CML. Vérifiez que le lab est démarré.';
            } else if (errorMessage.includes('401') || errorMessage.includes('Token')) {
                toastMessage = 'Token CML expiré. Veuillez vous reconnecter.';
            } else if (errorMessage.includes('404')) {
                toastMessage = 'Endpoint console non trouvé. Vérifiez la version de CML.';
            }
            
            toast.error(toastMessage);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupère toutes les sessions console actives
     */
    const getSessions = useCallback(async (): Promise<ConsoleSessionsListResponse | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch('/api/console/sessions', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data: ConsoleSessionsListResponse = await response.json();
            return data;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des sessions';
            setError(errorMessage);
            toast.error(errorMessage);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Ferme une session console
     */
    const closeSession = useCallback(async (sessionId: string): Promise<boolean> => {
        setLoading(true);
        setError(null);

        const csrf = getCsrfToken();
        if (!csrf) {
            const errorMsg = 'Jeton CSRF introuvable';
            setError(errorMsg);
            toast.error(errorMsg);
            return false;
        }

        try {
            const response = await fetch(`/api/console/sessions/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la fermeture de la session';
            setError(errorMessage);
            toast.error(errorMessage);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupère le log d'une console spécifique
     */
    const getConsoleLog = useCallback(async (
        labId: string,
        nodeId: string,
        consoleId: string
    ): Promise<ConsoleLogResponse | null> => {
        setLoading(true);
        setError(null);

        try {
            console.log('📥 Récupération du log console:', { labId, nodeId, consoleId });
            
            // Timeout de 35 secondes (30s backend + 5s marge)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 35000);

            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/consoles/${consoleId}/log`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData?.error || `Erreur ${response.status}: ${response.statusText}`;
                
                console.error('❌ Erreur lors de la récupération du log:', {
                    status: response.status,
                    error: errorData,
                });
                
                // Message plus explicite pour les timeouts
                if (response.status === 504 || errorData?.is_timeout) {
                    throw new Error('Le serveur CML ne répond pas dans les délais impartis. Le lab est peut-être en cours de démarrage.');
                }
                
                throw new Error(errorMessage);
            }

            const data: ConsoleLogResponse = await response.json();
            console.log('✅ Log récupéré avec succès:', { 
                hasLog: !!data.log,
                logType: typeof data.log,
            });
            return data;
        } catch (err) {
            let errorMessage = 'Erreur lors de la récupération du log';
            
            if (err instanceof Error) {
                if (err.name === 'AbortError') {
                    errorMessage = 'Timeout: Le serveur CML ne répond pas dans les délais impartis (35 secondes).';
                } else {
                    errorMessage = err.message;
                }
            }
            
            setError(errorMessage);
            console.error('❌ Erreur getConsoleLog:', err);
            
            // Ne pas afficher de toast pour les erreurs silencieuses (polling)
            // Le toast sera affiché par le composant si nécessaire
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        loading,
        error,
        getNodeConsoles,
        createSession,
        getSessions,
        closeSession,
        getConsoleLog,
    };
};

// Export des types pour utilisation dans d'autres composants
export type {
    ConsoleResponse,
    ConsoleSessionResponse,
    ConsoleSessionsListResponse,
    ConsoleLogResponse,
    CreateSessionPayload,
};

