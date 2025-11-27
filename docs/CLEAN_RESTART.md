# Nettoyage du Cache et Redémarrage

## Commandes Rapides

### Nettoyer tous les caches Laravel
```bash
php artisan optimize:clear
```

### Nettoyer et optimiser
```bash
php artisan app:clean-restart
```

### Nettoyer manuellement
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Recréer les caches optimisés
```bash
php artisan config:cache
php artisan route:cache
```

### Régénérer l'autoload Composer
```bash
composer dump-autoload
```

## Redémarrer les Services

### Serveur Laravel
```bash
php artisan serve
```

### Serveur Vite (Frontend)
```bash
npm run dev
```

### Les deux en même temps
```bash
npm run dev
# Dans un autre terminal:
php artisan serve
```

## Commandes Créées

### `php artisan app:clean-restart`
Commande personnalisée qui nettoie tous les caches et optimise l'application.

**Actions effectuées:**
- ✅ Nettoie tous les caches (config, route, view, cache, events, compiled)
- ✅ Recrée les caches optimisés (config, route)
- ⚠️ Note: Pour régénérer l'autoload, exécutez `composer dump-autoload` séparément

## Après le Nettoyage

1. **Vérifier que tout fonctionne:**
   ```bash
   php artisan route:list
   php artisan config:show app
   ```

2. **Redémarrer les serveurs:**
   - Laravel: `php artisan serve`
   - Vite: `npm run dev`

3. **Vider le cache du navigateur:**
   - Ctrl+Shift+R (Windows/Linux)
   - Cmd+Shift+R (Mac)

## Problèmes Courants

### Cache qui ne se vide pas
```bash
php artisan optimize:clear
rm -rf bootstrap/cache/*.php
php artisan config:cache
php artisan route:cache
```

### Routes qui ne se mettent pas à jour
```bash
php artisan route:clear
php artisan route:cache
```

### Configuration qui ne se met pas à jour
```bash
php artisan config:clear
php artisan config:cache
```


