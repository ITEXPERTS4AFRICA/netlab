#!/bin/bash

set -e

echo "🚀 Démarrage de NetLab..."

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-netlab}
DB_USERNAME=${DB_USERNAME:-netlab}
DB_PASSWORD=${DB_PASSWORD:-password}

until PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q' 2>/dev/null; do
    echo "⏳ Base de données non disponible, attente..."
    sleep 2
done

echo "✅ Base de données disponible!"

# Exécuter les migrations
echo "🔄 Exécution des migrations..."
php artisan migrate --force || echo "⚠️  Erreur lors des migrations (peut être normal si déjà exécutées)"

# Exécuter les seeders en développement
if [ "$APP_ENV" != "production" ]; then
    echo "🌱 Exécution des seeders (développement)..."
    php artisan db:seed --force || echo "⚠️  Erreur lors des seeders (peut être normal)"
fi

# Optimiser Laravel
echo "⚡ Optimisation de Laravel..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Créer les liens symboliques
echo "🔗 Création des liens symboliques..."
php artisan storage:link || true

# Définir les permissions
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ NetLab est prêt!"

# S'assurer que les répertoires de logs existent
mkdir -p /var/log/supervisor
chmod 755 /var/log/supervisor

# Exécuter la commande passée
exec "$@"

