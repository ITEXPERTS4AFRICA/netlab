/**
 * Système de gestion des erreurs centralisé
 * Types d'erreurs et gestionnaires
 */

export enum ErrorType {
    NETWORK = 'NETWORK',
    API = 'API',
    VALIDATION = 'VALIDATION',
    AUTHENTICATION = 'AUTHENTICATION',
    AUTHORIZATION = 'AUTHORIZATION',
    NOT_FOUND = 'NOT_FOUND',
    SERVER = 'SERVER',
    CLIENT = 'CLIENT',
    UNKNOWN = 'UNKNOWN',
}

export enum ErrorSeverity {
    LOW = 'LOW',
    MEDIUM = 'MEDIUM',
    HIGH = 'HIGH',
    CRITICAL = 'CRITICAL',
}

export interface AppError {
    type: ErrorType;
    severity: ErrorSeverity;
    message: string;
    code?: string;
    details?: Record<string, unknown>;
    originalError?: Error | unknown;
    timestamp: Date;
    context?: Record<string, unknown>;
}

/**
 * Créer une erreur structurée
 */
export function createError(
    type: ErrorType,
    message: string,
    options?: {
        severity?: ErrorSeverity;
        code?: string;
        details?: Record<string, unknown>;
        originalError?: Error | unknown;
        context?: Record<string, unknown>;
    }
): AppError {
    return {
        type,
        severity: options?.severity ?? ErrorSeverity.MEDIUM,
        message,
        code: options?.code,
        details: options?.details,
        originalError: options?.originalError,
        timestamp: new Date(),
        context: options?.context,
    };
}

/**
 * Analyser une erreur et créer un AppError
 */
export function parseError(error: unknown, context?: Record<string, unknown>): AppError {
    // Si c'est déjà un AppError, le retourner
    if (error && typeof error === 'object' && 'type' in error && 'severity' in error) {
        return error as AppError;
    }

    // Erreur Axios/HTTP
    if (error && typeof error === 'object' && 'response' in error) {
        const httpError = error as { response?: { status: number; data?: unknown }; message?: string };
        const status = httpError.response?.status ?? 0;
        
        if (status === 401) {
            return createError(
                ErrorType.AUTHENTICATION,
                'Vous devez être connecté pour effectuer cette action.',
                {
                    severity: ErrorSeverity.HIGH,
                    code: 'UNAUTHORIZED',
                    originalError: error,
                    context,
                }
            );
        }
        
        if (status === 403) {
            return createError(
                ErrorType.AUTHORIZATION,
                'Vous n\'avez pas les permissions nécessaires pour cette action.',
                {
                    severity: ErrorSeverity.HIGH,
                    code: 'FORBIDDEN',
                    originalError: error,
                    context,
                }
            );
        }
        
        if (status === 404) {
            return createError(
                ErrorType.NOT_FOUND,
                'La ressource demandée n\'a pas été trouvée.',
                {
                    severity: ErrorSeverity.MEDIUM,
                    code: 'NOT_FOUND',
                    originalError: error,
                    context,
                }
            );
        }
        
        if (status === 422) {
            return createError(
                ErrorType.VALIDATION,
                'Les données fournies sont invalides.',
                {
                    severity: ErrorSeverity.MEDIUM,
                    code: 'VALIDATION_ERROR',
                    details: httpError.response?.data as Record<string, unknown>,
                    originalError: error,
                    context,
                }
            );
        }
        
        if (status >= 500) {
            return createError(
                ErrorType.SERVER,
                'Une erreur serveur s\'est produite. Veuillez réessayer plus tard.',
                {
                    severity: ErrorSeverity.HIGH,
                    code: 'SERVER_ERROR',
                    originalError: error,
                    context,
                }
            );
        }
        
        return createError(
            ErrorType.API,
            httpError.message || 'Une erreur API s\'est produite.',
            {
                severity: ErrorSeverity.MEDIUM,
                code: `HTTP_${status}`,
                originalError: error,
                context,
            }
        );
    }

    // Erreur réseau
    if (error && typeof error === 'object' && 'request' in error && !('response' in error)) {
        return createError(
            ErrorType.NETWORK,
            'Erreur de connexion réseau. Vérifiez votre connexion internet.',
            {
                severity: ErrorSeverity.HIGH,
                code: 'NETWORK_ERROR',
                originalError: error,
                context,
            }
        );
    }

    // Erreur JavaScript standard
    if (error instanceof Error) {
        return createError(
            ErrorType.UNKNOWN,
            error.message || 'Une erreur inattendue s\'est produite.',
            {
                severity: ErrorSeverity.MEDIUM,
                originalError: error,
                context,
            }
        );
    }

    // Erreur inconnue
    return createError(
        ErrorType.UNKNOWN,
        'Une erreur inattendue s\'est produite.',
        {
            severity: ErrorSeverity.MEDIUM,
            originalError: error,
            context,
        }
    );
}

/**
 * Obtenir un message utilisateur-friendly à partir d'une erreur
 */
export function getUserFriendlyMessage(error: AppError | unknown): string {
    const appError = error instanceof Error || (error && typeof error === 'object' && 'type' in error)
        ? parseError(error)
        : createError(ErrorType.UNKNOWN, 'Une erreur inattendue s\'est produite.');

    // Messages spécifiques selon le type
    switch (appError.type) {
        case ErrorType.NETWORK:
            return 'Problème de connexion. Vérifiez votre connexion internet et réessayez.';
        
        case ErrorType.AUTHENTICATION:
            return 'Votre session a expiré. Veuillez vous reconnecter.';
        
        case ErrorType.AUTHORIZATION:
            return 'Vous n\'avez pas les permissions nécessaires pour cette action.';
        
        case ErrorType.NOT_FOUND:
            return 'La ressource demandée n\'existe pas ou a été supprimée.';
        
        case ErrorType.VALIDATION:
            if (appError.details && typeof appError.details === 'object' && 'errors' in appError.details) {
                const errors = appError.details.errors as Record<string, string[]>;
                const firstError = Object.values(errors)[0]?.[0];
                if (firstError) {
                    return firstError;
                }
            }
            return 'Les données fournies sont invalides. Veuillez vérifier et réessayer.';
        
        case ErrorType.SERVER:
            return 'Le serveur rencontre des difficultés. Veuillez réessayer dans quelques instants.';
        
        case ErrorType.API:
            // Messages spécifiques pour les erreurs API connues
            if (appError.code === 'CONNECTION_TIMEOUT') {
                return 'Le service de paiement ne répond pas. Veuillez réessayer plus tard.';
            }
            if (appError.code === 'INVALID_URL') {
                return 'Configuration incorrecte. Veuillez contacter le support.';
            }
            return appError.message;
        
        default:
            return appError.message;
    }
}

/**
 * Logger une erreur (pour développement et production)
 */
export function logError(error: AppError, additionalContext?: Record<string, unknown>) {
    const logData = {
        type: error.type,
        severity: error.severity,
        message: error.message,
        code: error.code,
        details: error.details,
        context: { ...error.context, ...additionalContext },
        timestamp: error.timestamp.toISOString(),
        stack: error.originalError instanceof Error ? error.originalError.stack : undefined,
    };

    // En développement, toujours logger dans la console
    if (import.meta.env.DEV) {
        console.error('AppError:', logData);
    }

    // En production, envoyer à un service de logging
    if (import.meta.env.PROD) {
        // TODO: Implémenter l'envoi à un service de logging
        // fetch('/api/errors', { method: 'POST', body: JSON.stringify(logData) })
    }
}

