# Système de feedbacks amélioré

## Vue d'ensemble

Le système de feedbacks a été entièrement refondu pour offrir une expérience cohérente et professionnelle pour les utilisateurs et les administrateurs. Il utilise le système d'erreurs structuré et des composants React modernes.

## Architecture

### 1. FeedbackManager

Composant centralisé qui gère tous les types de feedbacks :
- **Flash messages** du serveur (success, error, warning, info)
- **Toasts** avec icônes appropriées
- **Erreurs de validation** globales

### 2. Hook useFeedback

Hook React pour afficher des feedbacks de manière cohérente :

```tsx
const { showSuccess, showError, showWarning, showInfo, showLoading, dismiss } = useFeedback();
```

### 3. Trait ProvidesFeedback (Laravel)

Trait pour les contrôleurs Laravel qui simplifie l'envoi de messages de feedback :

```php
use App\Traits\ProvidesFeedback;

class UserController extends Controller
{
    use ProvidesFeedback;

    public function store(Request $request)
    {
        // ...
        return $this->success('Utilisateur créé avec succès', $user);
    }
}
```

## Utilisation

### Côté Frontend (React)

#### Utilisation basique

```tsx
import { useFeedback, FeedbackMessages } from '@/components/FeedbackManager';

function MyComponent() {
    const { showSuccess, showError } = useFeedback();

    const handleSubmit = async () => {
        try {
            await submitData();
            showSuccess(FeedbackMessages.SUCCESS.CREATED('Utilisateur'));
        } catch (error) {
            showError(error);
        }
    };
}
```

#### Avec Inertia Forms

```tsx
const { post, processing } = useForm({...});

const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/admin/users', {
        onSuccess: () => {
            showSuccess(FeedbackMessages.SUCCESS.CREATED('Utilisateur'));
        },
        onError: (errors) => {
            showError(errors.message || 'Erreur lors de la création');
        },
    });
};
```

#### Messages prédéfinis

```tsx
import { FeedbackMessages } from '@/components/FeedbackManager';

// Succès
showSuccess(FeedbackMessages.SUCCESS.CREATED('Utilisateur'));
showSuccess(FeedbackMessages.SUCCESS.UPDATED('Configuration'));
showSuccess(FeedbackMessages.SUCCESS.DELETED('Réservation'));
showSuccess(FeedbackMessages.SUCCESS.PAYMENT_SUCCESS);
showSuccess(FeedbackMessages.SUCCESS.RESERVATION_CREATED);

// Erreurs
showError(FeedbackMessages.ERROR.CREATION_FAILED('Utilisateur'));
showError(FeedbackMessages.ERROR.NETWORK_ERROR);
showError(FeedbackMessages.ERROR.UNAUTHORIZED);

// Avertissements
showWarning(FeedbackMessages.WARNING.UNSAVED_CHANGES);
showWarning(FeedbackMessages.WARNING.SESSION_EXPIRING);

// Informations
showInfo(FeedbackMessages.INFO.LOADING);
showInfo(FeedbackMessages.INFO.PROCESSING);
```

### Côté Backend (Laravel)

#### Utilisation du trait ProvidesFeedback

```php
use App\Traits\ProvidesFeedback;

class UserController extends Controller
{
    use ProvidesFeedback;

    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        $user = User::create($validated);

        return $this->success('Utilisateur créé avec succès', $user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([...]);
        $user->update($validated);

        return $this->success('Utilisateur mis à jour avec succès');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->success('Utilisateur supprimé avec succès');
    }
}
```

#### Méthodes disponibles

- `$this->success($message, $data = null, $status = 200)` - Message de succès
- `$this->error($message, $errors = null, $status = 400)` - Message d'erreur
- `$this->warning($message, $data = null, $status = 200)` - Avertissement
- `$this->info($message, $data = null, $status = 200)` - Information

#### Flash messages classiques

```php
// Succès
return redirect()->back()->with('success', 'Opération réussie');

// Erreur
return redirect()->back()->with('error', 'Une erreur est survenue');

// Avertissement
return redirect()->back()->with('warning', 'Attention: ...');

// Information
return redirect()->back()->with('info', 'Information: ...');
```

## Types de feedbacks

### 1. Toasts (Notifications)

Affichés en bas à gauche de l'écran avec :
- **Icônes** appropriées selon le type
- **Durée** configurable
- **Animation** d'apparition/disparition

### 2. Flash Messages

Messages du serveur affichés automatiquement via le `FeedbackManager` :
- `success` - Succès (vert, icône CheckCircle)
- `error` - Erreur (rouge, icône XCircle)
- `warning` - Avertissement (jaune, icône AlertTriangle)
- `info` - Information (bleu, icône Info)

### 3. Erreurs de validation

Affichées automatiquement si présentes dans `page.props.errors` :
- Format structuré avec le composant `ErrorDisplay`
- Messages utilisateur-friendly

## Configuration

### Middleware Inertia

Le middleware `HandleInertiaRequests` partage automatiquement les flash messages :

```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
    'warning' => fn () => $request->session()->get('warning'),
    'info' => fn () => $request->session()->get('info'),
],
```

### Layout

Le `FeedbackManager` est intégré dans `AppLayout` pour gérer automatiquement tous les feedbacks.

## Bonnes pratiques

### 1. Messages clairs et actionnables

✅ **Bon** :
```tsx
showError('Impossible de créer l\'utilisateur. Vérifiez que l\'email n\'est pas déjà utilisé.');
```

❌ **Mauvais** :
```tsx
showError('Erreur');
```

### 2. Utiliser les messages prédéfinis

✅ **Bon** :
```tsx
showSuccess(FeedbackMessages.SUCCESS.CREATED('Utilisateur'));
```

❌ **Mauvais** :
```tsx
showSuccess('Utilisateur créé');
```

### 3. Gérer les erreurs avec le système d'erreurs

✅ **Bon** :
```tsx
const { showError } = useFeedback();
try {
    await apiCall();
} catch (error) {
    showError(error); // Parse automatiquement l'erreur
}
```

### 4. Feedback immédiat

✅ **Bon** :
```tsx
const toastId = showLoading('Enregistrement en cours...');
try {
    await save();
    dismiss(toastId);
    showSuccess('Enregistré avec succès');
} catch (error) {
    dismiss(toastId);
    showError(error);
}
```

## Exemples complets

### Page admin - Création d'utilisateur

```tsx
import { useFeedback, FeedbackMessages } from '@/components/FeedbackManager';

export default function UserCreate() {
    const { showSuccess, showError } = useFeedback();
    const { post, processing, errors } = useForm({...});

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/users', {
            onSuccess: () => {
                showSuccess(FeedbackMessages.SUCCESS.CREATED('Utilisateur'));
            },
            onError: (errors) => {
                showError(errors.message || FeedbackMessages.ERROR.CREATION_FAILED('Utilisateur'));
            },
        });
    };

    return (
        <form onSubmit={submit}>
            {/* ... */}
        </form>
    );
}
```

### Contrôleur Laravel

```php
use App\Traits\ProvidesFeedback;

class UserController extends Controller
{
    use ProvidesFeedback;

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([...]);
            $user = User::create($validated);
            
            return $this->success('Utilisateur créé avec succès', $user);
        } catch (\Exception $e) {
            return $this->error('Impossible de créer l\'utilisateur', ['exception' => $e->getMessage()]);
        }
    }
}
```

## Migration

Pour migrer les pages existantes :

1. **Importer le hook** :
```tsx
import { useFeedback, FeedbackMessages } from '@/components/FeedbackManager';
```

2. **Utiliser le hook** :
```tsx
const { showSuccess, showError } = useFeedback();
```

3. **Remplacer les toasts manuels** :
```tsx
// Avant
toast.success('Succès');

// Après
showSuccess(FeedbackMessages.SUCCESS.CREATED('Ressource'));
```

4. **Mettre à jour les contrôleurs** :
```php
// Ajouter le trait
use ProvidesFeedback;

// Remplacer les redirects
return redirect()->back()->with('success', '...');
// Par
return $this->success('...');
```

## Support

Pour toute question ou problème, consultez :
- `resources/js/components/FeedbackManager.tsx` - Composant principal
- `app/Traits/ProvidesFeedback.php` - Trait Laravel
- `resources/js/utils/error-handler.ts` - Système d'erreurs

