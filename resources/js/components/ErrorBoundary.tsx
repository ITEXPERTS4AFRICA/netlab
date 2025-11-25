import React, { Component, ReactNode, ErrorInfo } from 'react';
import { AlertTriangle, RefreshCw, Home, Bug } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ReactNode;
    onError?: (error: Error, errorInfo: ErrorInfo) => void;
    showDetails?: boolean;
    resetOnNavigation?: boolean;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
    errorInfo: ErrorInfo | null;
    errorId: string;
}

/**
 * Error Boundary global pour capturer les erreurs React
 * Affiche une interface claire avec options de récupération
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    private resetTimeout: NodeJS.Timeout | null = null;

    constructor(props: ErrorBoundaryProps) {
        super(props);
        this.state = {
            hasError: false,
            error: null,
            errorInfo: null,
            errorId: '',
        };
    }

    static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
        // Générer un ID unique pour cette erreur
        const errorId = `ERR_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        
        return {
            hasError: true,
            error,
            errorId,
        };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        // Log l'erreur
        console.error('ErrorBoundary caught an error:', error, errorInfo);
        
        // Appeler le callback si fourni
        this.props.onError?.(error, errorInfo);
        
        // Enregistrer dans l'état
        this.setState({
            error,
            errorInfo,
        });

        // Optionnel: Envoyer à un service de logging
        if (import.meta.env.PROD) {
            // En production, envoyer à un service de logging
            this.logErrorToService(error, errorInfo);
        }
    }

    componentDidUpdate() {
        // Réinitialiser l'erreur lors de la navigation si activé
        // Note: La réinitialisation se fait via le router d'Inertia
        if (this.props.resetOnNavigation && this.state.hasError) {
            // L'erreur sera réinitialisée automatiquement lors de la navigation
        }
    }

    componentWillUnmount() {
        if (this.resetTimeout) {
            clearTimeout(this.resetTimeout);
        }
    }

    private logErrorToService(error: Error, errorInfo: ErrorInfo) {
        // TODO: Implémenter l'envoi à un service de logging (Sentry, LogRocket, etc.)
        try {
            // Exemple: fetch('/api/errors', { method: 'POST', body: JSON.stringify({ error, errorInfo }) })
            // Pour l'instant, on log juste dans la console
            console.log('Error logged:', { error, errorInfo });
        } catch (logError) {
            console.error('Failed to log error to service:', logError);
        }
    }

    private resetError = () => {
        this.setState({
            hasError: false,
            error: null,
            errorInfo: null,
            errorId: '',
        });
    };

    private handleReload = () => {
        window.location.reload();
    };

    private handleGoHome = () => {
        router.visit('/');
    };

    private handleRetry = () => {
        this.resetError();
    };

    private copyErrorDetails = () => {
        const errorDetails = {
            message: this.state.error?.message,
            stack: this.state.error?.stack,
            componentStack: this.state.errorInfo?.componentStack,
            errorId: this.state.errorId,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href,
        };

        const text = JSON.stringify(errorDetails, null, 2);
        navigator.clipboard.writeText(text).then(() => {
            alert('Détails de l\'erreur copiés dans le presse-papiers');
        }).catch(() => {
            console.error('Failed to copy error details');
        });
    };

    render() {
        if (this.state.hasError) {
            // Si un fallback personnalisé est fourni, l'utiliser
            if (this.props.fallback) {
                return this.props.fallback;
            }

            const { error, errorInfo, errorId } = this.state;
            const showDetails = this.props.showDetails ?? import.meta.env.DEV;

            return (
                <div className="min-h-screen flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900">
                    <Card className="w-full max-w-2xl">
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
                                    <AlertTriangle className="w-6 h-6 text-red-600 dark:text-red-400" />
                                </div>
                                <div>
                                    <CardTitle className="text-xl">Une erreur s'est produite</CardTitle>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        ID d'erreur: {errorId}
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-900/30 rounded-lg p-4">
                                <p className="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
                                    Message d'erreur:
                                </p>
                                <p className="text-sm text-red-700 dark:text-red-300 font-mono">
                                    {error?.message || 'Erreur inconnue'}
                                </p>
                            </div>

                            {showDetails && error?.stack && (
                                <details className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                    <summary className="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Détails techniques (développement)
                                    </summary>
                                    <pre className="text-xs text-gray-600 dark:text-gray-400 overflow-auto max-h-64 mt-2">
                                        {error.stack}
                                    </pre>
                                    {errorInfo?.componentStack && (
                                        <details className="mt-2">
                                            <summary className="cursor-pointer text-xs text-gray-500 dark:text-gray-500">
                                                Stack du composant
                                            </summary>
                                            <pre className="text-xs text-gray-600 dark:text-gray-400 overflow-auto max-h-32 mt-2">
                                                {errorInfo.componentStack}
                                            </pre>
                                        </details>
                                    )}
                                </details>
                            )}

                            <div className="flex flex-wrap gap-2 pt-4">
                                <Button onClick={this.handleRetry} variant="default">
                                    <RefreshCw className="w-4 h-4 mr-2" />
                                    Réessayer
                                </Button>
                                <Button onClick={this.handleGoHome} variant="outline">
                                    <Home className="w-4 h-4 mr-2" />
                                    Accueil
                                </Button>
                                {showDetails && (
                                    <Button onClick={this.copyErrorDetails} variant="outline">
                                        <Bug className="w-4 h-4 mr-2" />
                                        Copier les détails
                                    </Button>
                                )}
                                <Button onClick={this.handleReload} variant="outline">
                                    <RefreshCw className="w-4 h-4 mr-2" />
                                    Recharger la page
                                </Button>
                            </div>

                            <div className="text-xs text-muted-foreground pt-2 border-t">
                                <p>
                                    Si le problème persiste, contactez le support avec l'ID d'erreur: <code className="bg-gray-100 dark:bg-gray-800 px-1 rounded">{errorId}</code>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            );
        }

        return this.props.children;
    }
}

/**
 * Hook pour utiliser l'Error Boundary dans les composants fonctionnels
 */
export function useErrorHandler() {
    const throwError = React.useCallback((error: Error | string) => {
        if (typeof error === 'string') {
            throw new Error(error);
        }
        throw error;
    }, []);

    return { throwError };
}

