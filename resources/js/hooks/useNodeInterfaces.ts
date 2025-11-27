/**
 * Hook pour récupérer les interfaces et liens d'un node
 */

import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export interface NodeInterface {
    id: string;
    label?: string;
    type?: string;
    is_connected?: boolean;
    state?: string;
    mac_address?: string;
    node?: string;
}

export interface NodeLink {
    id: string;
    node_a?: string;
    node_b?: string;
    interface_a?: string;
    interface_b?: string;
    n1?: string;
    n2?: string;
    i1?: string;
    i2?: string;
    state?: string;
    interface1?: NodeInterface;
    interface2?: NodeInterface;
}

export const useNodeInterfaces = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [interfaces, setInterfaces] = useState<NodeInterface[]>([]);
    const [links, setLinks] = useState<NodeLink[]>([]);

    /**
     * Récupère les interfaces d'un node
     */
    const getNodeInterfaces = useCallback(async (
        labId: string,
        nodeId: string
    ): Promise<NodeInterface[] | null> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/nodes/${nodeId}/interfaces`, {
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

            const data = await response.json();
            const interfacesList = Array.isArray(data.interfaces) ? data.interfaces : [];
            setInterfaces(interfacesList);
            return interfacesList;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des interfaces';
            setError(errorMessage);
            console.error('Erreur getNodeInterfaces:', err);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Récupère les liens d'un lab (filtrés par node si nécessaire)
     */
    const getNodeLinks = useCallback(async (
        labId: string,
        nodeId?: string
    ): Promise<NodeLink[] | null> => {
        setLoading(true);
        setError(null);

        try {
            const url = nodeId 
                ? `/api/labs/${labId}/links?node_id=${nodeId}`
                : `/api/labs/${labId}/links`;
            
            const response = await fetch(url, {
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

            const data = await response.json();
            const linksList = Array.isArray(data.links) ? data.links : [];
            setLinks(linksList);
            return linksList;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la récupération des liens';
            setError(errorMessage);
            console.error('Erreur getNodeLinks:', err);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Connecter (démarrer) une interface
     */
    const connectInterface = useCallback(async (
        labId: string,
        interfaceId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            console.log('🔌 Tentative de connexion d\'interface:', { labId, interfaceId });
            
            const response = await fetch(`/api/labs/${labId}/interfaces/${interfaceId}/connect`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            console.log('📡 Réponse reçue:', { status: response.status, ok: response.ok });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('❌ Erreur API:', errorData);
                throw new Error(errorData?.error || `Erreur ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Succès:', data);
            toast.success(data.message || 'Interface connectée avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la connexion de l\'interface';
            setError(errorMessage);
            toast.error(errorMessage);
            console.error('❌ Erreur connectInterface:', err);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Déconnecter (arrêter) une interface
     */
    const disconnectInterface = useCallback(async (
        labId: string,
        interfaceId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            console.log('🔌 Tentative de déconnexion d\'interface:', { labId, interfaceId });
            
            const response = await fetch(`/api/labs/${labId}/interfaces/${interfaceId}/disconnect`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            console.log('📡 Réponse reçue:', { status: response.status, ok: response.ok });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('❌ Erreur API:', errorData);
                throw new Error(errorData?.error || `Erreur ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Succès:', data);
            toast.success(data.message || 'Interface déconnectée avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la déconnexion de l\'interface';
            setError(errorMessage);
            toast.error(errorMessage);
            console.error('❌ Erreur disconnectInterface:', err);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Connecter (démarrer) un lien
     */
    const connectLink = useCallback(async (
        labId: string,
        linkId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/links/${linkId}/connect`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            toast.success(data.message || 'Lien connecté avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la connexion du lien';
            setError(errorMessage);
            toast.error(errorMessage);
            console.error('Erreur connectLink:', err);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Déconnecter (arrêter) un lien
     */
    const disconnectLink = useCallback(async (
        labId: string,
        linkId: string
    ): Promise<boolean> => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/api/labs/${labId}/links/${linkId}/disconnect`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            const data = await response.json();
            toast.success(data.message || 'Lien déconnecté avec succès');
            return true;
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erreur lors de la déconnexion du lien';
            setError(errorMessage);
            toast.error(errorMessage);
            console.error('Erreur disconnectLink:', err);
            return false;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        loading,
        error,
        interfaces,
        links,
        getNodeInterfaces,
        getNodeLinks,
        connectInterface,
        disconnectInterface,
        connectLink,
        disconnectLink,
    };
};

