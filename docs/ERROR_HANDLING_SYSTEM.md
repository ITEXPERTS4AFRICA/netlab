# Système de gestion des erreurs

## Vue d'ensemble

Le système de gestion des erreurs utilise des **Error Boundaries** React pour capturer et gérer les erreurs de manière claire et cohérente.

## Architecture

### 1. Error Boundary global

L'`ErrorBoundary` est intégré au niveau racine de l'application (`app.tsx`) pour capturer toutes les erreurs React.

**Fonctionnalités :**
- ✅ Capture toutes les erreurs React
- ✅ Affiche une interface claire avec options de récupération
- ✅ Génère un ID unique pour chaque erreur
- ✅ Réinitialisation automatique lors de la navigation
- ✅ Détails techniques en mode développement

### 2. Types d'erreurs

Le système définit plusieurs types d'erreurs :

```typescript
enum ErrorType {
    NETWORK = 'NETWORK',           // Erreurs de connexion
    API = 'API',                   // Erreurs API
    VALIDATION = 'VALIDATION',     // Erreurs de validation
    AUTHENTICATION = 'AUTHENTICATION',
    AUTHORIZATION = 'AUTHORIZATION',
    NOT_FOUND = 'NOT_FOUND',
    SERVER = 'SERVER',
    CLIENT = 'CLIENT',
    UNKNOWN = 'UNKNOWN',
}
```

### 3. Composants d'affichage

#### ErrorBoundary
Composant de classe qui capture les erreurs React.

```tsx
<ErrorBoundary resetOnNavigation showDetails={import.meta.env.DEV}>
    <YourComponent />
</ErrorBoundary>
```

#### ErrorDisplay
Composant pour afficher les erreurs de manière cohérente.

```tsx
<ErrorDisplay 
    error={error} 
    onDismiss={() => setError(null)}
    onRetry={handleRetry}
    variant="alert" // 'inline' | 'alert' | 'card'
/>
```

### 4. Utilitaires

#### parseError
Parse une erreur et crée un `AppError` structuré.

```typescript
const appError = parseError(error, { context: 'reservation' });
```

#### getUserFriendlyMessage
Obtient un message utilisateur-friendly à partir d'une erreur.

```typescript
const message = getUserFriendlyMessage(appError);
```

#### logError
Log une erreur (console en dev, service en prod).

```typescript
logError(appError, { additionalContext });
```

### 5. Hook useErrorHandler

Hook pour gérer les erreurs dans les composants.

```tsx
const { handleError, handleApiError, withErrorHandling } = useErrorHandler();

// Gérer une erreur
try {
    await someAsyncOperation();
} catch (error) {
    handleApiError(error, { context: 'reservation' });
}

// Wrapper une fonction
const safeFunction = withErrorHandling(myAsyncFunction, {
    showToast: true,
    context: { source: 'payment' },
});
```

## Utilisation

### Dans un composant

```tsx
import { useErrorHandler } from '@/hooks/useErrorHandler';
import { ErrorDisplay } from '@/components/ErrorDisplay';

function MyComponent() {
    const { handleApiError } = useErrorHandler();
    const [error, setError] = useState(null);

    const handleSubmit = async () => {
        try {
            await submitData();
        } catch (err) {
            const appError = handleApiError(err, { context: 'submit' });
            setError(appError);
        }
    };

    return (
        <div>
            {error && (
                <ErrorDisplay 
                    error={error}
                    onDismiss={() => setError(null)}
                    onRetry={handleSubmit}
                />
            )}
            {/* ... */}
        </div>
    );
}
```

### Avec Error Boundary local

```tsx
import { ErrorBoundary } from '@/components/ErrorBoundary';

function MyComponent() {
    return (
        <ErrorBoundary fallback={<CustomErrorUI />}>
            <ComplexComponent />
        </ErrorBoundary>
    );
}
```

## Messages d'erreur

Les messages sont automatiquement traduits en messages utilisateur-friendly :

- **NETWORK** : "Problème de connexion. Vérifiez votre connexion internet et réessayez."
- **AUTHENTICATION** : "Votre session a expiré. Veuillez vous reconnecter."
- **AUTHORIZATION** : "Vous n'avez pas les permissions nécessaires."
- **VALIDATION** : Messages spécifiques depuis les erreurs de validation
- **SERVER** : "Le serveur rencontre des difficultés. Veuillez réessayer dans quelques instants."

## Logging

En développement :
- Toutes les erreurs sont loggées dans la console

En production :
- Les erreurs peuvent être envoyées à un service de logging (à implémenter)
- Les détails techniques sont masqués aux utilisateurs

## Bonnes pratiques

1. **Utiliser ErrorBoundary pour les composants critiques**
   ```tsx
   <ErrorBoundary>
       <PaymentForm />
   </ErrorBoundary>
   ```

2. **Utiliser useErrorHandler pour les erreurs API**
   ```tsx
   const { handleApiError } = useErrorHandler();
   ```

3. **Afficher les erreurs avec ErrorDisplay**
   ```tsx
   <ErrorDisplay error={error} variant="alert" />
   ```

4. **Fournir du contexte**
   ```tsx
   handleApiError(error, { 
       context: { 
           action: 'create_reservation',
           labId: lab.id 
       } 
   });
   ```

## Exemples

### Gestion d'erreur dans un formulaire

```tsx
const { handleValidationError } = useErrorHandler();
const [errors, setErrors] = useState<Record<string, string>>({});

const handleSubmit = async (data: FormData) => {
    try {
        await submitForm(data);
    } catch (error) {
        const appError = handleValidationError(error);
        if (appError.details?.errors) {
            setErrors(appError.details.errors as Record<string, string>);
        }
    }
};
```

### Gestion d'erreur réseau

```tsx
const { handleNetworkError } = useErrorHandler();

const fetchData = async () => {
    try {
        const response = await fetch('/api/data');
        return await response.json();
    } catch (error) {
        handleNetworkError(error, { endpoint: '/api/data' });
        throw error;
    }
};
```

## Configuration

L'ErrorBoundary global est configuré dans `app.tsx` :

```tsx
<ErrorBoundary 
    resetOnNavigation 
    showDetails={import.meta.env.DEV}
>
    <App {...props} />
</ErrorBoundary>
```

- `resetOnNavigation` : Réinitialise l'erreur lors de la navigation
- `showDetails` : Affiche les détails techniques (stack trace, etc.)

