# 🐳 Guide de Dockerisation - NetLab

## 📋 Vue d'ensemble

NetLab est maintenant entièrement dockerisé avec :
- **PostgreSQL** : Base de données
- **Redis** : Cache et queues
- **PHP-FPM + Nginx** : Application Laravel
- **Node.js** : Serveur de développement Vite
- **Queue Worker** : Traitement des queues Laravel
- **Scheduler** : Planificateur de tâches Laravel

## 🚀 Démarrage rapide

### Option 1 : Script automatique (Recommandé)

**Windows (PowerShell):**
```powershell
.\scripts\docker-start.ps1
```

**Linux/Mac:**
```bash
chmod +x scripts/docker-start.sh
./scripts/docker-start.sh
```

### Option 2 : Commandes manuelles

```bash
# 1. Copier le fichier .env si nécessaire
cp .env.example .env

# 2. Générer la clé d'application
docker-compose run --rm app php artisan key:generate

# 3. Construire et démarrer les services
docker-compose build
docker-compose up -d

# 4. Vérifier l'état
docker-compose ps
```

## ✅ Migrations automatiques

Les migrations sont **automatiquement exécutées** au démarrage du conteneur via le script `docker/entrypoint.sh`.

- ✅ Migrations exécutées à chaque démarrage (même en production)
- ✅ Seeders exécutés uniquement en développement
- ✅ Gestion automatique des erreurs

## 🔧 Configuration

### Variables d'environnement importantes

Dans votre fichier `.env` :

```env
# Base de données
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=netlab
DB_USERNAME=netlab
DB_PASSWORD=password

# Application
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:... # Généré automatiquement

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# CML
CISCO_CML_URL=https://votre-serveur-cml
CISCO_CML_USERNAME=votre-username
CISCO_CML_PASSWORD=votre-password
```

## 📊 Services disponibles

| Service | Port | Description |
|---------|------|-------------|
| **app** | 8000 | Application web (Nginx + PHP-FPM) |
| **node** | 5173 | Serveur de développement Vite |
| **postgres** | 5432 | Base de données PostgreSQL |
| **redis** | 6379 | Cache et queues Redis |
| **queue** | - | Worker de queues Laravel |
| **scheduler** | - | Planificateur de tâches Laravel |

## 🛠️ Commandes utiles

### Gestion des conteneurs

```bash
# Démarrer tous les services
docker-compose up -d

# Arrêter tous les services
docker-compose down

# Redémarrer un service spécifique
docker-compose restart app

# Voir les logs
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f queue
```

### Artisan (Laravel)

```bash
# Exécuter une commande Artisan
docker-compose exec app php artisan <commande>

# Exécuter les migrations manuellement
docker-compose exec app php artisan migrate

# Exécuter les seeders
docker-compose exec app php artisan db:seed

# Vider le cache
docker-compose exec app php artisan optimize:clear
```

### Base de données

```bash
# Accéder à PostgreSQL
docker-compose exec postgres psql -U netlab -d netlab

# Sauvegarder la base de données
docker-compose exec postgres pg_dump -U netlab netlab > backup.sql

# Restaurer la base de données
docker-compose exec -T postgres psql -U netlab netlab < backup.sql
```

### NPM/Vite

```bash
# Installer les dépendances
docker-compose exec node npm install

# Mode développement
docker-compose exec node npm run dev

# Build de production
docker-compose exec node npm run build
```

## 🔄 Mise à jour de la base de données

Les migrations sont exécutées automatiquement au démarrage. Pour forcer une mise à jour :

```bash
# Redémarrer le service app (déclenchera les migrations)
docker-compose restart app

# Ou exécuter manuellement
docker-compose exec app php artisan migrate --force
```

## 🐛 Dépannage

### Problème de permissions

```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Base de données non accessible

```bash
# Vérifier les logs PostgreSQL
docker-compose logs postgres

# Vérifier la connexion
docker-compose exec app php artisan tinker
# Puis: DB::connection()->getPdo();
```

### Reconstruire les images

```bash
# Reconstruire sans cache
docker-compose build --no-cache

# Reconstruire et redémarrer
docker-compose up -d --build
```

### Nettoyer Docker

```bash
# Arrêter et supprimer les volumes (⚠️ supprime les données)
docker-compose down -v

# Nettoyer les images non utilisées
docker system prune -a
```

## 📦 Production

Pour la production, utilisez le fichier `docker-compose.prod.yml` :

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Cela activera :
- ✅ Cache des configurations Laravel
- ✅ Mode production (APP_DEBUG=false)
- ✅ Optimisations de performance

## 📚 Documentation supplémentaire

- [Docker README](docker/README.md)
- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Docker](https://docs.docker.com/)


