# Dépannage : ERR_BLOCKED_BY_CLIENT

## Problème

Les erreurs `ERR_BLOCKED_BY_CLIENT` indiquent que quelque chose bloque les requêtes vers le serveur Vite (`localhost:5173`). Rien ne s'affiche dans le navigateur.

## Causes principales

1. **Extensions de navigateur** (le plus fréquent)
   - AdBlockers (uBlock Origin, AdBlock Plus, etc.)
   - Extensions de confidentialité (Privacy Badger, Ghostery, etc.)
   - Extensions de sécurité

2. **Antivirus/Firewall**
   - Blocage des connexions locales
   - Protection en temps réel trop stricte

3. **Paramètres de sécurité du navigateur**
   - Mode strict de sécurité
   - Politiques de groupe (entreprise)

4. **Proxy/VPN**
   - Proxy qui bloque localhost
   - VPN avec filtrage strict

## Solutions (par ordre de priorité)

### Solution 1 : Désactiver les extensions de navigateur

#### Chrome/Edge
1. Ouvrir `chrome://extensions/` (ou `edge://extensions/`)
2. Désactiver temporairement :
   - uBlock Origin
   - AdBlock Plus
   - Privacy Badger
   - Ghostery
   - Toute extension de blocage de publicité
3. Recharger la page (Ctrl+Shift+R)

#### Firefox
1. Ouvrir `about:addons`
2. Désactiver les extensions de blocage
3. Recharger la page (Ctrl+Shift+R)

### Solution 2 : Ajouter localhost aux exceptions

#### uBlock Origin
1. Cliquer sur l'icône uBlock
2. Cliquer sur l'engrenage (paramètres)
3. Aller dans "Filtres personnalisés"
4. Ajouter :
   ```
   @@||localhost:5173^
   @@||127.0.0.1:5173^
   ```

#### AdBlock Plus
1. Cliquer sur l'icône AdBlock
2. Paramètres → Filtres personnalisés
3. Ajouter :
   ```
   @@||localhost:5173^
   ```

### Solution 3 : Mode navigation privée (test rapide)

Ouvrir une fenêtre de navigation privée (Ctrl+Shift+N) et tester si ça fonctionne. Si oui, c'est bien une extension qui bloque.

### Solution 4 : Vérifier l'antivirus/firewall

#### Windows Defender
1. Ouvrir "Sécurité Windows"
2. Protection contre les virus et menaces
3. Gérer les paramètres → Exclusions
4. Ajouter le dossier du projet en exclusion

#### Firewall Windows
1. Ouvrir "Pare-feu Windows Defender"
2. Paramètres avancés
3. Règles de trafic entrant → Nouvelle règle
4. Autoriser le port 5173

### Solution 5 : Changer le port Vite

Si le problème persiste, changer le port dans `vite.config.ts` :

```typescript
server: {
    host: '0.0.0.0',
    port: 3000, // Changer de 5173 à 3000
    strictPort: true,
    hmr: {
        host: 'localhost',
        port: 3000, // Même port
    },
},
```

Puis redémarrer Vite :
```bash
npm run dev
```

### Solution 6 : Utiliser 127.0.0.1 au lieu de localhost

Modifier `vite.config.ts` :

```typescript
server: {
    host: '127.0.0.1', // Au lieu de '0.0.0.0'
    port: 5173,
    strictPort: true,
    hmr: {
        host: '127.0.0.1', // Au lieu de 'localhost'
        port: 5173,
    },
},
```

### Solution 7 : Vérifier que Vite tourne

```bash
# Vérifier que le processus Vite est actif
ps aux | grep vite

# Vérifier que le port est ouvert
netstat -an | grep 5173
# ou
lsof -i :5173
```

Si Vite ne tourne pas :
```bash
npm run dev
```

## Vérification rapide

1. ✅ Vite tourne-t-il ? (`ps aux | grep vite`)
2. ✅ Le port 5173 est-il accessible ? (ouvrir http://localhost:5173 dans le navigateur)
3. ✅ Les extensions sont-elles désactivées ?
4. ✅ Le cache du navigateur est-il vidé ? (Ctrl+Shift+R)

## Test de diagnostic

Ouvrir la console du navigateur (F12) et vérifier :

```javascript
// Tester si localhost:5173 est accessible
fetch('http://localhost:5173/@vite/client')
  .then(r => console.log('✅ Vite accessible'))
  .catch(e => console.error('❌ Vite bloqué:', e));
```

## Solution temporaire : Build de production

Si vous devez tester rapidement sans Vite :

```bash
# Build de production
npm run build

# Le serveur Laravel servira les assets compilés
php artisan serve
```

⚠️ **Note** : Cette solution ne permet pas le Hot Module Replacement (HMR).

## Prévention

Pour éviter ce problème à l'avenir :

1. **Créer un profil de navigateur dédié au développement**
   - Chrome : Créer un nouveau profil sans extensions
   - Firefox : Créer un profil de développement

2. **Utiliser un navigateur dédié au développement**
   - Chrome Canary
   - Firefox Developer Edition

3. **Configurer les exceptions dans les extensions**
   - Ajouter `localhost:5173` et `127.0.0.1:5173` aux exceptions

## Commandes utiles

```bash
# Vérifier que Vite tourne
ps aux | grep vite

# Tuer tous les processus Vite
pkill -f vite

# Redémarrer Vite
npm run dev

# Nettoyer le cache Vite
rm -rf node_modules/.vite
npm run dev
```

