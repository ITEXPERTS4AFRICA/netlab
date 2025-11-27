/**
 * Système de notification pour les mises à jour de configuration
 * Permet de notifier les composants quand la configuration est mise à jour
 */

type ConfigUpdateListener = (labId: string) => void;

class ConfigUpdateNotifier {
    private listeners: Set<ConfigUpdateListener> = new Set();

    /**
     * S'abonner aux mises à jour de configuration
     */
    subscribe(listener: ConfigUpdateListener): () => void {
        this.listeners.add(listener);
        // Retourner une fonction de désabonnement
        return () => {
            this.listeners.delete(listener);
        };
    }

    /**
     * Notifier tous les listeners qu'une configuration a été mise à jour
     */
    notify(labId: string): void {
        this.listeners.forEach((listener) => {
            try {
                listener(labId);
            } catch (error) {
                console.error('Erreur dans le listener de mise à jour de configuration:', error);
            }
        });
    }

    /**
     * Obtenir le nombre de listeners actifs
     */
    getListenerCount(): number {
        return this.listeners.size;
    }
}

// Instance singleton
export const configUpdateNotifier = new ConfigUpdateNotifier();

/**
 * Hook React pour écouter les mises à jour de configuration
 */
export function useConfigUpdateListener(
    labId: string | null,
    onUpdate: (labId: string) => void | Promise<void>
) {
    const { useEffect } = require('react');

    useEffect(() => {
        if (!labId) return;

        const unsubscribe = configUpdateNotifier.subscribe(async (updatedLabId) => {
            if (updatedLabId === labId) {
                await onUpdate(updatedLabId);
            }
        });

        return unsubscribe;
    }, [labId, onUpdate]);
}


