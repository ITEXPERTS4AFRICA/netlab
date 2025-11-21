#!/bin/bash

echo "🚀 Lancement du projet NetLab"
echo "=============================="
echo ""

# Fonction pour vérifier si une commande existe
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo "❌ $1 n'est pas installé"
        return 1
    fi
    return 0
}

# 1. Vérifier PHP
echo "1. Vérification de PHP..."
if ! check_command php; then
    echo "   Installez PHP 8.2+"
    exit 1
fi
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
echo "   ✅ PHP $PHP_VERSION"
echo ""

# 2. Vérifier Composer
echo "2. Vérification de Composer..."
if ! check_command composer; then
    echo "   Installez Composer: https://getcomposer.org/download/"
    exit 1
fi
echo "   ✅ Composer installé"
echo ""

# 3. Vérifier Node.js
echo "3. Vérification de Node.js..."
if ! check_command node; then
    echo "   Installez Node.js 20+"
    exit 1
fi
NODE_VERSION=$(node --version)
echo "   ✅ Node.js $NODE_VERSION"
echo ""

# 4. Vérifier les dépendances PHP
echo "4. Vérification des dépendances PHP..."
if [ ! -d "vendor" ]; then
    echo "   📦 Installation des dépendances PHP..."
    composer install
else
    echo "   ✅ Dépendances PHP installées"
fi
echo ""

# 5. Vérifier les dépendances Node.js
echo "5. Vérification des dépendances Node.js..."
if [ ! -d "node_modules" ]; then
    echo "   📦 Installation des dépendances Node.js..."
    npm install
else
    echo "   ✅ Dépendances Node.js installées"
fi
echo ""

# 6. Vérifier le fichier .env
echo "6. Vérification du fichier .env..."
if [ ! -f ".env" ]; then
    echo "   ⚠️  Fichier .env non trouvé"
    if [ -f ".env.example" ]; then
        echo "   📝 Création de .env depuis .env.example..."
        cp .env.example .env
    else
        echo "   ⚠️  .env.example non trouvé. Création d'un .env basique..."
        cat > .env <<EOF
APP_NAME=NetLab
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=file
QUEUE_CONNECTION=sync
EOF
    fi
else
    echo "   ✅ Fichier .env existe"
fi
echo ""

# 7. Vérifier la clé d'application
echo "7. Vérification de la clé d'application..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   🔑 Génération de la clé d'application..."
    php artisan key:generate
else
    echo "   ✅ Clé d'application configurée"
fi
echo ""

# 8. Créer la base de données SQLite si nécessaire
if grep -q "DB_CONNECTION=sqlite" .env 2>/dev/null; then
    echo "8. Vérification de la base de données SQLite..."
    if [ ! -f "database/database.sqlite" ]; then
        echo "   📦 Création de la base de données SQLite..."
        touch database/database.sqlite
        php artisan migrate --force
    else
        echo "   ✅ Base de données SQLite existe"
    fi
    echo ""
fi

# 9. Lancer le projet
echo "🌟 Lancement du serveur de développement..."
echo ""
echo "Le projet sera accessible sur: http://localhost:8000"
echo ""
echo "Pour arrêter le serveur, appuyez sur Ctrl+C"
echo ""
echo "=============================="
echo ""

# Lancer avec composer dev (qui lance serveur PHP, queue, et Vite)
composer dev

