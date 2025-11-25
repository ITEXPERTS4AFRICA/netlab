import React, { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { CheckCircle2, XCircle, Info, AlertTriangle, Loader2 } from 'lucide-react';
import { ErrorDisplay } from './ErrorDisplay';
import { parseError, getUserFriendlyMessage, ErrorType } from '@/utils/error-handler';

interface FlashMessages {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

interface FeedbackManagerProps {
    children: React.ReactNode;
    showGlobalErrors?: boolean;
}

/**
 * Composant centralisé pour gérer tous les feedbacks (toasts, flash messages, erreurs)
 * Utilise le système d'erreurs structuré pour une expérience cohérente
 */
export function FeedbackManager({ children, showGlobalErrors = true }: FeedbackManagerProps) {
    const page = usePage();
    const flash = (page.props.flash as FlashMessages) || {};

    // Gérer les flash messages du serveur
    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success, {
                icon: <CheckCircle2 className="h-4 w-4" />,
                duration: 5000,
            });
        }
    }, [flash.success]);

    useEffect(() => {
        if (flash.error) {
            // Parser l'erreur pour obtenir un message utilisateur-friendly
            const error = parseError(flash.error);
            const message = getUserFriendlyMessage(error);
            
            toast.error(message, {
                icon: <XCircle className="h-4 w-4" />,
                duration: 6000,
            });
        }
    }, [flash.error]);

    useEffect(() => {
        if (flash.warning) {
            toast.warning(flash.warning, {
                icon: <AlertTriangle className="h-4 w-4" />,
                duration: 5000,
            });
        }
    }, [flash.warning]);

    useEffect(() => {
        if (flash.info) {
            toast.info(flash.info, {
                icon: <Info className="h-4 w-4" />,
                duration: 4000,
            });
        }
    }, [flash.info]);

    return (
        <>
            {children}
            {/* Afficher les erreurs globales si activé */}
            {showGlobalErrors && page.props.errors && Object.keys(page.props.errors).length > 0 && (
                <div className="fixed bottom-4 right-4 z-50 max-w-md">
                    <ErrorDisplay
                        error={{
                            type: ErrorType.VALIDATION,
                            message: 'Des erreurs de validation ont été détectées',
                            details: { errors: page.props.errors },
                        } as any}
                        variant="alert"
                    />
                </div>
            )}
        </>
    );
}

/**
 * Hook pour afficher des feedbacks de manière cohérente
 */
export function useFeedback() {
    const showSuccess = React.useCallback((message: string, options?: { duration?: number }) => {
        toast.success(message, {
            icon: <CheckCircle2 className="h-4 w-4" />,
            duration: options?.duration ?? 5000,
        });
    }, []);

    const showError = React.useCallback((error: unknown, options?: { duration?: number; context?: Record<string, unknown> }) => {
        const appError = parseError(error, options?.context);
        const message = getUserFriendlyMessage(appError);
        
        toast.error(message, {
            icon: <XCircle className="h-4 w-4" />,
            duration: options?.duration ?? 6000,
        });
        
        return appError;
    }, []);

    const showWarning = React.useCallback((message: string, options?: { duration?: number }) => {
        toast.warning(message, {
            icon: <AlertTriangle className="h-4 w-4" />,
            duration: options?.duration ?? 5000,
        });
    }, []);

    const showInfo = React.useCallback((message: string, options?: { duration?: number }) => {
        toast.info(message, {
            icon: <Info className="h-4 w-4" />,
            duration: options?.duration ?? 4000,
        });
    }, []);

    const showLoading = React.useCallback((message: string) => {
        return toast.loading(message, {
            icon: <Loader2 className="h-4 w-4 animate-spin" />,
        });
    }, []);

    const dismiss = React.useCallback((toastId: string | number) => {
        toast.dismiss(toastId);
    }, []);

    return {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showLoading,
        dismiss,
    };
}

/**
 * Messages de feedback prédéfinis pour les actions courantes
 */
export const FeedbackMessages = {
    // Succès
    SUCCESS: {
        CREATED: (resource: string) => `${resource} créé(e) avec succès`,
        UPDATED: (resource: string) => `${resource} mis(e) à jour avec succès`,
        DELETED: (resource: string) => `${resource} supprimé(e) avec succès`,
        SAVED: (resource: string) => `${resource} enregistré(e) avec succès`,
        SENT: (resource: string) => `${resource} envoyé(e) avec succès`,
        CONNECTED: 'Connexion réussie',
        DISCONNECTED: 'Déconnexion réussie',
        PAYMENT_SUCCESS: 'Paiement effectué avec succès',
        RESERVATION_CREATED: 'Réservation créée avec succès',
        RESERVATION_CANCELLED: 'Réservation annulée avec succès',
        CONFIG_SAVED: 'Configuration enregistrée avec succès',
    },
    // Erreurs
    ERROR: {
        CREATION_FAILED: (resource: string) => `Impossible de créer ${resource}`,
        UPDATE_FAILED: (resource: string) => `Impossible de mettre à jour ${resource}`,
        DELETION_FAILED: (resource: string) => `Impossible de supprimer ${resource}`,
        LOAD_FAILED: (resource: string) => `Impossible de charger ${resource}`,
        SAVE_FAILED: (resource: string) => `Impossible d'enregistrer ${resource}`,
        NETWORK_ERROR: 'Erreur de connexion. Vérifiez votre connexion internet.',
        UNAUTHORIZED: 'Vous n\'avez pas les permissions nécessaires',
        NOT_FOUND: 'Ressource non trouvée',
        VALIDATION_ERROR: 'Les données fournies sont invalides',
        PAYMENT_FAILED: 'Le paiement a échoué',
        RESERVATION_FAILED: 'Impossible de créer la réservation',
    },
    // Avertissements
    WARNING: {
        UNSAVED_CHANGES: 'Vous avez des modifications non enregistrées',
        CONFIRM_DELETE: (resource: string) => `Êtes-vous sûr de vouloir supprimer ${resource} ?`,
        SESSION_EXPIRING: 'Votre session expire bientôt',
        PAYMENT_PENDING: 'Paiement en attente de confirmation',
    },
    // Informations
    INFO: {
        LOADING: 'Chargement en cours...',
        PROCESSING: 'Traitement en cours...',
        SAVING: 'Enregistrement en cours...',
        NO_DATA: 'Aucune donnée disponible',
        SELECT_ITEM: 'Veuillez sélectionner un élément',
    },
};

