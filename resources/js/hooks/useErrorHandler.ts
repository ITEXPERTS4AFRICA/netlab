import { useCallback } from 'react';
import { parseError, logError, getUserFriendlyMessage, type AppError, ErrorType } from '@/utils/error-handler';
import { toast } from 'sonner';

/**
 * Hook pour gérer les erreurs de manière cohérente dans les composants
 */
export function useErrorHandler() {
    /**
     * Gérer une erreur et afficher un message utilisateur
     */
    const handleError = useCallback((error: unknown, options?: {
        showToast?: boolean;
        logError?: boolean;
        context?: Record<string, unknown>;
    }) => {
        const appError = parseError(error, options?.context);
        
        // Logger l'erreur si demandé
        if (options?.logError !== false) {
            logError(appError, options?.context);
        }
        
        // Afficher un toast si demandé
        if (options?.showToast !== false) {
            const message = getUserFriendlyMessage(appError);
            toast.error(message, {
                duration: 5000,
            });
        }
        
        return appError;
    }, []);

    /**
     * Gérer une erreur API spécifiquement
     */
    const handleApiError = useCallback((error: unknown, context?: Record<string, unknown>) => {
        return handleError(error, {
            showToast: true,
            logError: true,
            context: { ...context, source: 'API' },
        });
    }, [handleError]);

    /**
     * Gérer une erreur réseau
     */
    const handleNetworkError = useCallback((error: unknown, context?: Record<string, unknown>) => {
        return handleError(error, {
            showToast: true,
            logError: true,
            context: { ...context, source: 'NETWORK' },
        });
    }, [handleError]);

    /**
     * Gérer une erreur de validation
     */
    const handleValidationError = useCallback((error: unknown, context?: Record<string, unknown>) => {
        return handleError(error, {
            showToast: true,
            logError: false, // Les erreurs de validation ne nécessitent pas de log
            context: { ...context, source: 'VALIDATION' },
        });
    }, [handleError]);

    /**
     * Créer une fonction de gestion d'erreur pour les async functions
     */
    const withErrorHandling = useCallback(<T extends (...args: unknown[]) => Promise<unknown>>(
        fn: T,
        options?: {
            showToast?: boolean;
            logError?: boolean;
            context?: Record<string, unknown>;
        }
    ) => {
        return (async (...args: Parameters<T>) => {
            try {
                return await fn(...args);
            } catch (error) {
                handleError(error, options);
                throw error; // Re-throw pour permettre la gestion locale si nécessaire
            }
        }) as T;
    }, [handleError]);

    return {
        handleError,
        handleApiError,
        handleNetworkError,
        handleValidationError,
        withErrorHandling,
    };
}

