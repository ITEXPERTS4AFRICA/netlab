# Dépannage Vite - Erreurs de développement

## Erreur : "ERR_ABORTED 504 (Outdated Optimize Dep)"

Cette erreur signifie que les dépendances optimisées par Vite sont obsolètes.

### Solution rapide

1. **Arrêter le serveur Vite** (Ctrl+C dans le terminal où il tourne)

2. **Nettoyer le cache Vite** :
   ```bash
   rm -rf node_modules/.vite
   ```

3. **Redémarrer le serveur Vite** :
   ```bash
   npm run dev
   ```

### Solution complète

Si le problème persiste :

```bash
# 1. Arrêter tous les processus
# Ctrl+C dans le terminal

# 2. Nettoyer complètement
rm -rf node_modules/.vite
rm -rf public/hot
rm -rf public/build

# 3. Réinstaller les dépendances (optionnel, si nécessaire)
npm install

# 4. Redémarrer
npm run dev
```

## Erreur : "Failed to fetch dynamically imported module"

Cette erreur indique que Vite ne peut pas charger un module dynamique.

### Solutions

1. **Vérifier que le serveur Vite tourne** :
   ```bash
   ps aux | grep vite
   ```

2. **Vérifier le fichier `public/hot`** :
   ```bash
   cat public/hot
   # Doit contenir : http://localhost:5173
   ```

3. **Redémarrer Vite** :
   ```bash
   # Arrêter (Ctrl+C)
   npm run dev
   ```

4. **Vider le cache du navigateur** :
   - Chrome/Edge : Ctrl+Shift+R (ou Cmd+Shift+R sur Mac)
   - Firefox : Ctrl+F5 (ou Cmd+Shift+R sur Mac)

## Erreur : "Unchecked runtime.lastError"

Cette erreur dans la console du navigateur est **normale** et peut être **ignorée**.

- Elle est causée par une extension Chrome qui essaie de se connecter
- Elle n'affecte **pas** le fonctionnement de l'application
- **Solution** : Ignorez-la

## Erreur : "Failed to load tags" dans overlay.js

Cette erreur vient de l'overlay d'erreur de Vite et est généralement **sans impact** sur l'application.

### Cause
- L'overlay d'erreur de Vite essaie de charger des tags/templates qui ne sont pas disponibles
- C'est souvent dû à un problème de cache ou de chargement des ressources Vite

### Solutions

1. **Ignorer l'erreur** (recommandé) :
   - Cette erreur n'affecte pas le fonctionnement de l'application
   - L'application continue de fonctionner normalement

2. **Nettoyer le cache Vite** :
   ```bash
   # Arrêter Vite (Ctrl+C)
   rm -rf node_modules/.vite
   npm run dev
   ```

3. **Désactiver l'overlay en développement** (si nécessaire) :
   Modifier `vite.config.ts` :
   ```typescript
   export default defineConfig({
       // ...
       server: {
           // ...
           hmr: {
               host: 'localhost',
               port: 5173,
               overlay: false, // Désactiver l'overlay
           },
       },
   });
   ```

**Note** : L'erreur est généralement inoffensive et peut être ignorée en développement.

## Problèmes courants et solutions

### Le serveur Vite ne démarre pas

```bash
# Vérifier que le port 5173 n'est pas utilisé
lsof -i :5173

# Si occupé, tuer le processus
kill -9 <PID>

# Redémarrer
npm run dev
```

### Les changements ne se reflètent pas

1. **Vider le cache du navigateur** (Ctrl+Shift+R)
2. **Vérifier que HMR fonctionne** (Hot Module Replacement)
3. **Redémarrer Vite**

### Erreurs de dépendances manquantes

```bash
# Réinstaller les dépendances
rm -rf node_modules
npm install
```

### Problème avec recharts ou autres dépendances

```bash
# Forcer la réoptimisation
rm -rf node_modules/.vite
npm run dev
```

## Commandes utiles

```bash
# Vérifier l'état de Vite
ps aux | grep vite

# Voir les logs Vite
# (dans le terminal où npm run dev tourne)

# Nettoyer complètement
rm -rf node_modules/.vite public/hot public/build

# Redémarrer proprement
npm run dev
```

## Configuration Vite

Le fichier `vite.config.ts` est configuré pour :
- Port : 5173
- HMR : activé sur localhost:5173
- React : avec JSX automatique

Si vous modifiez le port, mettez à jour aussi `public/hot`.

## Vérification rapide

1. ✅ Le serveur Vite tourne-t-il ? (`ps aux | grep vite`)
2. ✅ Le fichier `public/hot` existe-t-il ? (`cat public/hot`)
3. ✅ Le port 5173 est-il accessible ? (ouvrir http://localhost:5173)
4. ✅ Le cache est-il propre ? (`rm -rf node_modules/.vite`)

## Si rien ne fonctionne

1. **Arrêter tous les processus** :
   ```bash
   pkill -f vite
   pkill -f "npm run dev"
   ```

2. **Nettoyer complètement** :
   ```bash
   rm -rf node_modules/.vite
   rm -rf public/hot
   rm -rf public/build
   ```

3. **Réinstaller** (si nécessaire) :
   ```bash
   npm install
   ```

4. **Redémarrer** :
   ```bash
   npm run dev
   ```

5. **Vider le cache du navigateur** et recharger la page

