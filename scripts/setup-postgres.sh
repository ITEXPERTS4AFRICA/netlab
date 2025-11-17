#!/bin/bash

echo "🐘 Configuration de PostgreSQL pour Laravel NetLab"
echo ""

# 1. Vérifier si PostgreSQL est installé
if ! command -v psql &> /dev/null; then
    echo "📦 Installation de PostgreSQL..."
    sudo apt update
    sudo apt install -y postgresql postgresql-contrib
    echo "✅ PostgreSQL installé"
else
    echo "✅ PostgreSQL déjà installé"
fi

# 2. Installer l'extension PHP pour PostgreSQL
echo ""
echo "📦 Installation de l'extension PHP pgsql..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
if [ -n "$PHP_VERSION" ]; then
    sudo apt install -y php${PHP_VERSION}-pgsql
    echo "✅ Extension PHP pgsql installée"
else
    echo "❌ PHP non trouvé"
    exit 1
fi

# 3. Démarrer le service PostgreSQL
echo ""
echo "🔄 Démarrage du service PostgreSQL..."
sudo systemctl start postgresql
sudo systemctl enable postgresql
echo "✅ Service PostgreSQL démarré"

# 4. Définir les informations de connexion selon le prompt
DB_NAME="netlab"
DB_USER="netlab"
DB_PASSWORD="netlab"
DB_HOST="127.0.0.1"
DB_PORT="5432"

# 5. Créer l'utilisateur et la base de données
echo ""
echo "🔧 Création de l'utilisateur et de la base de données..."
sudo -u postgres psql <<EOF
-- Créer l'utilisateur s'il n'existe pas
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_user WHERE usename = '$DB_USER') THEN
        CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
    ELSE
        ALTER USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
    END IF;
END
\$\$;

-- Créer la base de données si elle n'existe pas
SELECT 'CREATE DATABASE $DB_NAME OWNER $DB_USER'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec

-- Donner tous les privilèges à l'utilisateur
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\q
EOF

if [ $? -eq 0 ]; then
    echo "✅ Utilisateur et base de données créés"
else
    echo "⚠️  Erreur lors de la création. Vérifiez les permissions."
fi

# 6. Mettre à jour le fichier .env
echo ""
echo "📝 Mise à jour du fichier .env..."

# Vérifier si .env existe
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé. Création depuis .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "❌ .env.example non trouvé non plus. Création d'un .env basique..."
        cat > .env <<ENVFILE
APP_NAME=NetLab
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=netlab
DB_USERNAME=netlab
DB_PASSWORD=netlab
ENVFILE
    fi
fi

# Mettre à jour les variables DB dans .env
if [ -f .env ]; then
    # Sauvegarder le fichier .env
    cp .env .env.backup

    # Mettre à jour ou ajouter les variables DB
    if grep -q "^DB_CONNECTION=" .env; then
        sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    else
        echo "DB_CONNECTION=pgsql" >> .env
    fi

    if grep -q "^DB_HOST=" .env; then
        sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
    else
        echo "DB_HOST=$DB_HOST" >> .env
    fi

    if grep -q "^DB_PORT=" .env; then
        sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
    else
        echo "DB_PORT=$DB_PORT" >> .env
    fi

    if grep -q "^DB_DATABASE=" .env; then
        sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    else
        echo "DB_DATABASE=$DB_NAME" >> .env
    fi

    if grep -q "^DB_USERNAME=" .env; then
        sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    else
        echo "DB_USERNAME=$DB_USER" >> .env
    fi

    if grep -q "^DB_PASSWORD=" .env; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
    else
        echo "DB_PASSWORD=$DB_PASSWORD" >> .env
    fi

    echo "✅ Fichier .env mis à jour"
    echo ""
    echo "📋 Configuration sauvegardée dans .env.backup"
else
    echo "❌ Impossible de créer/mettre à jour .env"
    exit 1
fi

# 7. Tester la connexion
echo ""
echo "🔍 Test de la connexion à la base de données..."
if php artisan db:show 2>/dev/null; then
    echo "✅ Connexion réussie !"
else
    echo "⚠️  Impossible de tester la connexion. Vérifiez manuellement avec: php artisan db:show"
fi

echo ""
echo "✅ Configuration PostgreSQL terminée !"
echo ""
echo "Prochaines étapes :"
echo "1. Exécuter les migrations: php artisan migrate"
echo "2. (Optionnel) Exécuter les seeders: php artisan db:seed"
echo ""

