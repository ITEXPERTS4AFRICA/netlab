# 🐳 Guide Docker - NetLab

Guide rapide pour utiliser Docker avec NetLab.

## 🚀 Démarrage rapide

```bash
# 1. Démarrer tous les services
docker-compose up -d

# 2. Initialiser l'application
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed --class=AdminUserSeeder

# 3. Accéder à l'application
# http://localhost:8000
```

## 📋 Commandes essentielles

### Avec Make (si disponible)

```bash
make setup      # Configuration complète
make up         # Démarrer
make down       # Arrêter
make logs       # Voir les logs
make shell      # Shell dans le conteneur
make artisan CMD="migrate"  # Commande Artisan
```

### Avec Docker Compose

```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose down

# Logs
docker-compose logs -f app

# Shell
docker-compose exec app bash

# Artisan
docker-compose exec app php artisan migrate
```

## 🔧 Services

- **app** : Laravel (port 8000)
- **node** : Vite dev server (port 5173)
- **postgres** : PostgreSQL (port 5432)
- **redis** : Redis (port 6379)
- **queue** : Worker de queues
- **scheduler** : Planificateur de tâches

## 📚 Documentation complète

Voir [docker/README.md](./docker/README.md) pour la documentation détaillée.


