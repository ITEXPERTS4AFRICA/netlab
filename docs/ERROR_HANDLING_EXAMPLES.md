# Exemples d'utilisation du système de gestion des erreurs

## Exemple 1 : Utilisation dans un formulaire

```tsx
import { useErrorHandler } from '@/hooks/useErrorHandler';
import { ErrorDisplay } from '@/components/ErrorDisplay';
import { useState } from 'react';

function ReservationForm() {
    const { handleApiError, handleValidationError } = useErrorHandler();
    const [error, setError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (data: FormData) => {
        setIsSubmitting(true);
        setError(null);

        try {
            await router.post('/api/labs/reserve', data);
        } catch (err) {
            // Parser l'erreur
            const appError = parseError(err, { 
                context: { 
                    action: 'create_reservation',
                    labId: data.lab_id 
                } 
            });

            // Gérer selon le type
            if (appError.type === ErrorType.VALIDATION) {
                handleValidationError(err);
            } else {
                handleApiError(err, { context: 'reservation' });
            }

            setError(appError);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {error && (
                <ErrorDisplay 
                    error={error}
                    onDismiss={() => setError(null)}
                    onRetry={handleSubmit}
                    variant="alert"
                />
            )}
            {/* ... reste du formulaire */}
        </form>
    );
}
```

## Exemple 2 : Error Boundary local

```tsx
import { ErrorBoundary } from '@/components/ErrorBoundary';

function ComplexComponent() {
    return (
        <ErrorBoundary 
            fallback={
                <div className="p-4 border rounded">
                    <p>Une erreur s'est produite dans ce composant.</p>
                </div>
            }
        >
            <RiskyComponent />
        </ErrorBoundary>
    );
}
```

## Exemple 3 : Gestion d'erreur API avec retry

```tsx
import { useErrorHandler } from '@/hooks/useErrorHandler';
import { ErrorDisplay } from '@/components/ErrorDisplay';
import { useState } from 'react';

function PaymentComponent() {
    const { handleApiError } = useErrorHandler();
    const [error, setError] = useState(null);
    const [retryCount, setRetryCount] = useState(0);

    const processPayment = async () => {
        try {
            const response = await fetch('/api/payments/process', {
                method: 'POST',
                body: JSON.stringify(paymentData),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (err) {
            const appError = handleApiError(err, {
                context: {
                    action: 'process_payment',
                    retryCount,
                }
            });

            setError(appError);
            throw err;
        }
    };

    const handleRetry = async () => {
        setRetryCount(prev => prev + 1);
        setError(null);
        try {
            await processPayment();
        } catch {
            // Erreur déjà gérée par handleApiError
        }
    };

    return (
        <div>
            {error && (
                <ErrorDisplay 
                    error={error}
                    onRetry={retryCount < 3 ? handleRetry : undefined}
                    variant="card"
                />
            )}
        </div>
    );
}
```

## Exemple 4 : Wrapper avec gestion d'erreur automatique

```tsx
import { useErrorHandler } from '@/hooks/useErrorHandler';

function MyComponent() {
    const { withErrorHandling } = useErrorHandler();

    // Wrapper une fonction pour gérer automatiquement les erreurs
    const safeFetchData = withErrorHandling(
        async (id: string) => {
            const response = await fetch(`/api/data/${id}`);
            if (!response.ok) throw new Error('Failed to fetch');
            return response.json();
        },
        {
            showToast: true,
            context: { source: 'data_fetch' },
        }
    );

    const handleClick = async () => {
        try {
            const data = await safeFetchData('123');
            // Utiliser les données
        } catch {
            // Erreur déjà gérée et toast affiché
        }
    };

    return <button onClick={handleClick}>Fetch Data</button>;
}
```

## Exemple 5 : Gestion d'erreur réseau avec fallback

```tsx
import { useErrorHandler } from '@/hooks/useErrorHandler';
import { ErrorType } from '@/utils/error-handler';

function DataComponent() {
    const { handleNetworkError } = useErrorHandler();
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [isOffline, setIsOffline] = useState(false);

    const fetchData = async () => {
        try {
            const response = await fetch('/api/data');
            const result = await response.json();
            setData(result);
            setIsOffline(false);
        } catch (err) {
            const appError = handleNetworkError(err);
            
            if (appError.type === ErrorType.NETWORK) {
                setIsOffline(true);
                // Utiliser des données en cache si disponibles
                const cached = localStorage.getItem('cached_data');
                if (cached) {
                    setData(JSON.parse(cached));
                }
            }
            
            setError(appError);
        }
    };

    return (
        <div>
            {isOffline && (
                <div className="bg-yellow-50 border border-yellow-200 rounded p-2 mb-4">
                    Mode hors ligne - Données en cache
                </div>
            )}
            {error && <ErrorDisplay error={error} onRetry={fetchData} />}
            {/* ... */}
        </div>
    );
}
```

## Exemple 6 : Error Boundary avec fallback personnalisé

```tsx
import { ErrorBoundary } from '@/components/ErrorBoundary';

function App() {
    return (
        <ErrorBoundary
            fallback={
                <div className="min-h-screen flex items-center justify-center">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold mb-4">Oops !</h1>
                        <p className="text-gray-600 mb-4">
                            Une erreur inattendue s'est produite.
                        </p>
                        <button 
                            onClick={() => window.location.reload()}
                            className="px-4 py-2 bg-blue-500 text-white rounded"
                        >
                            Recharger la page
                        </button>
                    </div>
                </div>
            }
        >
            <YourApp />
        </ErrorBoundary>
    );
}
```

