# 🐳 NetLab - Configuration Docker

Ce dossier contient la configuration Docker pour NetLab.

## 📋 Prérequis

- Docker 20.10+
- Docker Compose 2.0+

## 🚀 Démarrage rapide

### 1. Configuration de l'environnement

Copiez le fichier `.env.example` vers `.env` et configurez les variables :

```bash
cp .env.example .env
```

Variables importantes :
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - Configuration PostgreSQL
- `APP_KEY` - Clé d'application Laravel (générée automatiquement)
- `CISCO_CML_URL`, `CISCO_CML_USERNAME`, `CISCO_CML_PASSWORD` - Configuration CML

### 2. Démarrer les services

```bash
# Construire et démarrer tous les services
docker-compose up -d

# Voir les logs
docker-compose logs -f

# Arrêter les services
docker-compose down
```

### 3. Initialiser l'application

```bash
# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Créer l'utilisateur admin
docker-compose exec app php artisan db:seed --class=AdminUserSeeder

# Créer le lien symbolique pour le stockage
docker-compose exec app php artisan storage:link
```

### 4. Accéder à l'application

- **Application** : http://localhost:8000
- **Vite Dev Server** : http://localhost:5173

## 🏗️ Architecture des services

### Services Docker

- **app** : Application Laravel (PHP-FPM + Nginx)
- **node** : Serveur de développement Vite (React)
- **postgres** : Base de données PostgreSQL
- **redis** : Cache et queues
- **queue** : Worker de queues Laravel
- **scheduler** : Planificateur de tâches Laravel (Cron)

### Ports

- `8000` : Application web (Nginx)
- `5173` : Vite dev server
- `5432` : PostgreSQL
- `6379` : Redis

## 📝 Commandes utiles

### Artisan

```bash
# Exécuter une commande Artisan
docker-compose exec app php artisan <commande>

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Exécuter les seeders
docker-compose exec app php artisan db:seed

# Vider le cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Composer

```bash
# Installer les dépendances
docker-compose exec app composer install

# Mettre à jour les dépendances
docker-compose exec app composer update
```

### NPM

```bash
# Installer les dépendances
docker-compose exec node npm install

# Compiler pour la production
docker-compose exec node npm run build

# Mode développement
docker-compose exec node npm run dev
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

### Logs

```bash
# Voir tous les logs
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f app
docker-compose logs -f node
docker-compose logs -f queue
```

## 🔧 Configuration

### PHP

La configuration PHP se trouve dans `docker/php/php.ini`.

### Nginx

La configuration Nginx se trouve dans `docker/nginx/default.conf`.

### Supervisor

Supervisor gère PHP-FPM et Nginx. Configuration dans `docker/supervisor/supervisord.conf`.

## 🧹 Maintenance

### Nettoyer les volumes

```bash
# Arrêter et supprimer les volumes (⚠️ supprime les données)
docker-compose down -v
```

### Reconstruire les images

```bash
# Reconstruire sans cache
docker-compose build --no-cache

# Reconstruire et redémarrer
docker-compose up -d --build
```

### Optimiser Docker

```bash
# Nettoyer les images non utilisées
docker system prune -a

# Nettoyer les volumes non utilisés
docker volume prune
```

## 🐛 Dépannage

### Problème de permissions

```bash
# Corriger les permissions du stockage
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Base de données non accessible

```bash
# Vérifier la connexion
docker-compose exec app php artisan db:monitor

# Vérifier les logs PostgreSQL
docker-compose logs postgres
```

### Cache Laravel

```bash
# Vider tous les caches
docker-compose exec app php artisan optimize:clear
```

## 📚 Documentation supplémentaire

- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Docker](https://docs.docker.com/)
- [Documentation Docker Compose](https://docs.docker.com/compose/)


