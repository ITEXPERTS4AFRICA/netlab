#!/bin/bash

echo "🚀 Démarrage du projet Laravel NetLab"
echo ""

# Charger nvm si disponible
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" 2>/dev/null

# Vérification de PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Veuillez exécuter ./install.sh d'abord."
    exit 1
fi

# Vérification de Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé. Veuillez exécuter ./install.sh d'abord."
    exit 1
fi

# Vérification de Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Node.js n'est pas installé. Veuillez exécuter ./install.sh d'abord."
    exit 1
fi

# Vérifier la version de Node.js
NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 20 ]; then
    echo "⚠️  Node.js version $(node --version) détectée. Le projet nécessite Node.js 20+."
    echo "Chargement de Node.js 20 via nvm..."
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    nvm use 20 2>/dev/null || nvm install 20 && nvm use 20
fi

# Vérification des dépendances
if [ ! -d "vendor" ]; then
    echo "📦 Installation des dépendances PHP..."
    composer install
fi

if [ ! -d "node_modules" ]; then
    echo "📦 Installation des dépendances Node.js..."
    npm install
fi

# Vérification de la clé d'application
if ! grep -q "APP_KEY=" .env 2>/dev/null || grep -q "APP_KEY=$" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate
fi

# Lancement du projet
echo ""
echo "🌟 Lancement du serveur de développement..."
echo "Le projet sera accessible sur http://localhost:8000"
echo ""
composer dev

