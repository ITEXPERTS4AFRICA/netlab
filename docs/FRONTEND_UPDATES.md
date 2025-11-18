# Mises à jour Frontend - Console API

## Résumé

Mise à jour des composants frontend pour utiliser des types TypeScript générés depuis l'OpenAPI CML et améliorer l'intégration avec les endpoints console.

## Fichiers créés

### 1. `resources/js/hooks/useConsole.ts`
Hook personnalisé React pour gérer tous les appels API console avec typage TypeScript complet.

**Fonctionnalités :**
- ✅ `getNodeConsoles()` - Récupère les consoles disponibles pour un nœud
- ✅ `createSession()` - Crée une session console
- ✅ `getSessions()` - Récupère toutes les sessions actives
- ✅ `closeSession()` - Ferme une session console
- ✅ `getConsoleLog()` - Récupère le log d'une console

**Avantages :**
- Typage TypeScript complet
- Gestion centralisée des erreurs
- Gestion automatique du CSRF token
- Messages d'erreur utilisateur-friendly avec toast notifications

## Fichiers modifiés

### 1. `resources/js/components/lab-console-panel.tsx`
Composant mis à jour pour utiliser le hook `useConsole`.

**Changements :**
- ✅ Remplacement des appels `fetch` directs par le hook `useConsole`
- ✅ Utilisation des types TypeScript générés
- ✅ Export des types `ConsoleSession` et `ConsoleSessionsResponse` pour réutilisation
- ✅ Code plus propre et maintenable
- ✅ Meilleure gestion des erreurs

**Avant :**
```typescript
const res = await fetch('/api/console/sessions', {
    method: 'POST',
    headers: { /* ... */ },
    body: JSON.stringify({ /* ... */ }),
});
```

**Après :**
```typescript
const data = await consoleApi.createSession({
    lab_id: cmlLabId,
    node_id: selectedNodeId,
    type,
});
```

## Types TypeScript

### Types exportés depuis `lab-console-panel.tsx`
- `ConsoleSession` - Type pour une session console
- `ConsoleSessionsResponse` - Type pour la réponse de liste de sessions

### Types depuis `useConsole.ts`
- `ConsoleResponse` - Réponse pour les consoles d'un nœud
- `ConsoleSessionResponse` - Réponse lors de la création d'une session
- `ConsoleSessionsListResponse` - Liste des sessions actives
- `ConsoleLogResponse` - Réponse pour le log d'une console
- `CreateSessionPayload` - Payload pour créer une session

## Intégration avec les types OpenAPI

Les types sont basés sur la spécification OpenAPI CML 2.9 et peuvent être étendus pour utiliser directement les types générés depuis `resources/js/types/cml-api.d.ts`.

## Prochaines étapes possibles

1. **Utiliser directement les types OpenAPI générés**
   - Remplacer les types manuels par ceux de `cml-api.d.ts`
   - Utiliser `ResponseType` helper pour extraire les types de réponse

2. **Créer d'autres hooks similaires**
   - `useLabs()` - Pour les opérations sur les labs
   - `useTopology()` - Pour la topologie des labs
   - `useAnnotations()` - Pour les annotations (déjà partiellement fait)

3. **Améliorer la gestion d'erreur**
   - Créer un système centralisé de gestion d'erreurs
   - Ajouter des retry automatiques pour les requêtes échouées

4. **Tests unitaires**
   - Créer des tests pour le hook `useConsole`
   - Tester les composants avec les nouveaux hooks

## Avantages de cette approche

1. **Type Safety** - Tous les appels API sont typés
2. **Réutilisabilité** - Le hook peut être utilisé dans d'autres composants
3. **Maintenabilité** - Code centralisé et facile à maintenir
4. **DX (Developer Experience)** - Autocomplétion et vérification de types
5. **Cohérence** - Même pattern pour tous les appels API

## Exemple d'utilisation

```typescript
import { useConsole } from '@/hooks/useConsole';

function MyComponent() {
    const consoleApi = useConsole();
    
    const handleCreateSession = async () => {
        const session = await consoleApi.createSession({
            lab_id: 'lab-123',
            node_id: 'node-456',
            type: 'console',
        });
        
        if (session) {
            console.log('Session créée:', session.session_id);
        }
    };
    
    return <button onClick={handleCreateSession}>Créer session</button>;
}
```



