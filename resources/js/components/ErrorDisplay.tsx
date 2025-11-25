import React from 'react';
import { AlertTriangle, XCircle, Info, AlertCircle, X } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AppError, ErrorType, getUserFriendlyMessage } from '@/utils/error-handler';
import { cn } from '@/lib/utils';

interface ErrorDisplayProps {
    error: AppError | Error | unknown;
    onDismiss?: () => void;
    onRetry?: () => void;
    className?: string;
    showDetails?: boolean;
    variant?: 'inline' | 'alert' | 'card';
}

/**
 * Composant pour afficher les erreurs de manière claire et cohérente
 */
export function ErrorDisplay({
    error,
    onDismiss,
    onRetry,
    className,
    showDetails = false,
    variant = 'alert',
}: ErrorDisplayProps) {
    // Parser l'erreur si nécessaire
    const appError = error instanceof Error || (error && typeof error === 'object' && 'type' in error)
        ? error as AppError
        : { type: ErrorType.UNKNOWN, message: String(error) } as Partial<AppError>;

    const message = getUserFriendlyMessage(appError);
    const isDismissible = !!onDismiss;

    // Icône selon le type d'erreur
    const getIcon = () => {
        switch (appError.type) {
            case ErrorType.NETWORK:
            case ErrorType.SERVER:
                return <XCircle className="h-4 w-4" />;
            case ErrorType.AUTHENTICATION:
            case ErrorType.AUTHORIZATION:
                return <AlertCircle className="h-4 w-4" />;
            case ErrorType.VALIDATION:
                return <Info className="h-4 w-4" />;
            default:
                return <AlertTriangle className="h-4 w-4" />;
        }
    };

    // Variante de couleur selon le type
    const getVariant = () => {
        switch (appError.type) {
            case ErrorType.NETWORK:
            case ErrorType.SERVER:
                return 'destructive';
            case ErrorType.AUTHENTICATION:
            case ErrorType.AUTHORIZATION:
                return 'default';
            case ErrorType.VALIDATION:
                return 'default';
            default:
                return 'destructive';
        }
    };

    if (variant === 'inline') {
        return (
            <div className={cn('flex items-start gap-2 text-sm', className)}>
                {getIcon()}
                <div className="flex-1">
                    <p className="text-red-600 dark:text-red-400">{message}</p>
                    {showDetails && appError.code && (
                        <p className="text-xs text-muted-foreground mt-1">Code: {appError.code}</p>
                    )}
                </div>
                {isDismissible && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onDismiss}
                        className="h-6 w-6 p-0"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>
        );
    }

    if (variant === 'card') {
        return (
            <div className={cn('rounded-lg border p-4', className)}>
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 mt-0.5">
                        {getIcon()}
                    </div>
                    <div className="flex-1 min-w-0">
                        <h3 className="text-sm font-medium">Erreur</h3>
                        <p className="text-sm text-muted-foreground mt-1">{message}</p>
                        {showDetails && appError.code && (
                            <p className="text-xs text-muted-foreground mt-2">
                                Code: <code>{appError.code}</code>
                            </p>
                        )}
                        {onRetry && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={onRetry}
                                className="mt-3"
                            >
                                Réessayer
                            </Button>
                        )}
                    </div>
                    {isDismissible && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onDismiss}
                            className="h-6 w-6 p-0 flex-shrink-0"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                </div>
            </div>
        );
    }

    // Variant 'alert' (par défaut)
    return (
        <Alert variant={getVariant()} className={className}>
            <div className="flex items-start gap-3">
                <div className="flex-shrink-0 mt-0.5">
                    {getIcon()}
                </div>
                <div className="flex-1 min-w-0">
                    <AlertTitle>Erreur</AlertTitle>
                    <AlertDescription className="mt-1">
                        {message}
                    </AlertDescription>
                    {showDetails && appError.code && (
                        <p className="text-xs text-muted-foreground mt-2">
                            Code: <code className="bg-muted px-1 rounded">{appError.code}</code>
                        </p>
                    )}
                    {onRetry && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={onRetry}
                            className="mt-3"
                        >
                            Réessayer
                        </Button>
                    )}
                </div>
                {isDismissible && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onDismiss}
                        className="h-6 w-6 p-0 flex-shrink-0"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </div>
        </Alert>
    );
}

/**
 * Hook pour gérer les erreurs dans les composants
 */
export function useErrorDisplay() {
    const [error, setError] = React.useState<AppError | null>(null);

    const setErrorFromException = React.useCallback((exception: unknown) => {
        // Parser l'erreur
        const appError = exception instanceof Error || (exception && typeof exception === 'object' && 'type' in exception)
            ? exception as AppError
            : { type: ErrorType.UNKNOWN, message: String(exception) } as AppError;
        
        setError(appError);
    }, []);

    const clearError = React.useCallback(() => {
        setError(null);
    }, []);

    return {
        error,
        setError: setErrorFromException,
        clearError,
        hasError: error !== null,
    };
}

