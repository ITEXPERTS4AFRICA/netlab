# Solution rapide : ERR_BLOCKED_BY_CLIENT

## 🚨 Problème
Rien ne s'affiche, erreurs `ERR_BLOCKED_BY_CLIENT` dans la console.

## ✅ Solution la plus rapide (30 secondes)

### Étape 1 : Désactiver les extensions de blocage

**Chrome/Edge :**
1. Ouvrir `chrome://extensions/` (ou `edge://extensions/`)
2. Désactiver **uBlock Origin** ou **AdBlock Plus**
3. Recharger la page (Ctrl+Shift+R)

**Firefox :**
1. Ouvrir `about:addons`
2. Désactiver les extensions de blocage
3. Recharger la page (Ctrl+Shift+R)

### Étape 2 : Tester en navigation privée

Ouvrir une fenêtre de navigation privée (Ctrl+Shift+N) et tester. Si ça fonctionne, c'est bien une extension qui bloque.

## 🔧 Solutions alternatives

### Option A : Ajouter localhost aux exceptions (uBlock Origin)

1. Cliquer sur l'icône uBlock
2. Paramètres (engrenage)
3. Filtres personnalisés
4. Ajouter :
   ```
   @@||localhost:5173^
   @@||127.0.0.1:5173^
   ```

### Option B : Changer le port Vite

Modifier `vite.config.ts` :
```typescript
server: {
    port: 3000, // Au lieu de 5173
    hmr: {
        port: 3000,
    },
},
```

Puis redémarrer : `npm run dev`

### Option C : Utiliser 127.0.0.1

La configuration a été mise à jour pour utiliser `127.0.0.1` au lieu de `localhost`. Redémarrer Vite :

```bash
# Arrêter Vite (Ctrl+C)
npm run dev
```

## 🧪 Test rapide

Ouvrir la console (F12) et tester :

```javascript
fetch('http://127.0.0.1:5173/@vite/client')
  .then(() => console.log('✅ Vite accessible'))
  .catch(e => console.error('❌ Bloqué:', e));
```

## 📋 Checklist

- [ ] Extensions de blocage désactivées
- [ ] Vite redémarré (`npm run dev`)
- [ ] Page rechargée (Ctrl+Shift+R)
- [ ] Test en navigation privée
- [ ] Port 5173 accessible (ouvrir http://127.0.0.1:5173)

## 🆘 Si rien ne fonctionne

Utiliser le build de production temporairement :

```bash
npm run build
php artisan serve
```

⚠️ Pas de HMR avec cette solution, mais l'application fonctionnera.

