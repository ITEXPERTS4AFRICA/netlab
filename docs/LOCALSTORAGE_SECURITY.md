# Problème de localStorage dans certains navigateurs

## Problème

Certains navigateurs (notamment Microsoft Edge dans certains contextes) peuvent bloquer l'accès à `localStorage` pour des raisons de sécurité, générant l'erreur :

```
SecurityError: Failed to read the 'localStorage' property from 'Window': Access is denied for this document.
```

## Causes possibles

1. **Mode privé/InPrivate** : Certains navigateurs désactivent localStorage en mode privé
2. **Politiques de sécurité strictes** : Paramètres de sécurité du navigateur ou de l'entreprise
3. **Iframes avec restrictions** : Iframes sans attribut `sandbox` approprié
4. **Fichiers locaux (file://)** : Certains navigateurs bloquent localStorage pour les fichiers locaux
5. **Extensions de navigateur** : Extensions de sécurité qui bloquent localStorage
6. **Paramètres de groupe (entreprise)** : Politiques de groupe qui désactivent localStorage

## Solution implémentée

Un système de **polyfill avec fallback en mémoire** a été mis en place :

### 1. Utilitaire sécurisé (`resources/js/utils/safe-storage.ts`)

- Vérifie la disponibilité de localStorage avant utilisation
- Utilise un stockage en mémoire comme fallback si localStorage est bloqué
- Gère silencieusement les erreurs de sécurité

### 2. Polyfill automatique

Le polyfill est activé automatiquement au démarrage de l'application (`resources/js/app.tsx`) :

```typescript
import { setupLocalStoragePolyfill } from './utils/safe-storage';
setupLocalStoragePolyfill();
```

### 3. Utilisation dans le code

Tous les accès à localStorage utilisent maintenant les fonctions sécurisées :

```typescript
import { safeGetItem, safeSetItem } from '@/utils/safe-storage';

// Au lieu de window.localStorage.getItem(key)
const value = safeGetItem('key', 'default');

// Au lieu de window.localStorage.setItem(key, value)
safeSetItem('key', 'value');
```

## Limitations du fallback mémoire

⚠️ **Important** : Le fallback en mémoire a des limitations :

- Les données sont perdues lors du rechargement de la page
- Les données ne sont pas partagées entre les onglets
- Les données ne persistent pas entre les sessions

Pour les données critiques, utilisez :
- **Cookies** (pour la persistance serveur)
- **SessionStorage** (pour la persistance par onglet)
- **API backend** (pour la persistance permanente)

## Vérification

Pour vérifier si le polyfill est actif, ouvrez la console du navigateur :

```javascript
// Si vous voyez ce message, le polyfill est actif :
// "localStorage polyfill activé (utilise le stockage en mémoire)"
```

## Recommandations

1. **Pour les utilisateurs** : Vérifiez les paramètres de sécurité de votre navigateur
2. **Pour les développeurs** : Utilisez toujours `safeGetItem` et `safeSetItem` au lieu d'accès directs à `localStorage`
3. **Pour les données critiques** : Privilégiez les cookies ou l'API backend

## Navigation compatible

Le système fonctionne maintenant avec :
- ✅ Chrome/Chromium
- ✅ Firefox
- ✅ Safari
- ✅ Edge (même avec restrictions)
- ✅ Mode privé (avec fallback mémoire)
- ✅ Iframes (avec fallback mémoire)

