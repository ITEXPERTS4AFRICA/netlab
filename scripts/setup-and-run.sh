#!/bin/bash

echo "🚀 Configuration complète et lancement du projet Laravel NetLab"
echo ""

# Charger nvm si disponible
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" 2>/dev/null

# 1. Installer les extensions PHP manquantes
echo "📦 Étape 1: Installation des extensions PHP..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)
if [ -n "$PHP_VERSION" ]; then
    echo "Version PHP: $PHP_VERSION"
    sudo apt install -y php${PHP_VERSION}-dom php${PHP_VERSION}-xml 2>/dev/null || {
        echo "⚠️  Impossible d'installer automatiquement. Veuillez exécuter:"
        echo "   sudo apt install -y php${PHP_VERSION}-dom php${PHP_VERSION}-xml"
        read -p "Appuyez sur Entrée après avoir installé les extensions..."
    }
else
    echo "❌ PHP non trouvé"
    exit 1
fi

# 2. Configurer Node.js 20
echo ""
echo "📦 Étape 2: Configuration de Node.js 20..."
if [ -d "$HOME/.nvm" ]; then
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

    # Vérifier si Node.js 20 est installé
    if nvm list | grep -q "v20"; then
        nvm use 20
        echo "✅ Node.js 20 activé: $(node --version)"
    else
        echo "Installation de Node.js 20..."
        nvm install 20
        nvm use 20
        nvm alias default 20
        echo "✅ Node.js 20 installé et activé: $(node --version)"
    fi
else
    echo "⚠️  nvm non trouvé. Installation de nvm..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/api/v0.39.0/install.sh | bash
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    nvm install 20
    nvm use 20
    nvm alias default 20
    echo "✅ Node.js 20 installé: $(node --version)"
fi

# 3. Installer les dépendances PHP
echo ""
echo "📦 Étape 3: Installation des dépendances PHP..."
if [ ! -d "vendor" ]; then
    composer install || {
        echo "❌ Erreur lors de l'installation des dépendances PHP"
        exit 1
    }
    echo "✅ Dépendances PHP installées"
else
    echo "✅ Dépendances PHP déjà installées"
fi

# 4. Installer les dépendances Node.js
echo ""
echo "📦 Étape 4: Installation des dépendances Node.js..."
if [ ! -d "node_modules" ]; then
    npm install || {
        echo "❌ Erreur lors de l'installation des dépendances Node.js"
        exit 1
    }
    echo "✅ Dépendances Node.js installées"
else
    echo "✅ Dépendances Node.js déjà installées"
fi

# 5. Vérifier la clé d'application
echo ""
echo "🔑 Étape 5: Vérification de la clé d'application..."
if ! grep -q "APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
    php artisan key:generate
    echo "✅ Clé d'application générée"
else
    echo "✅ Clé d'application déjà configurée"
fi

# 6. Lancer le projet
echo ""
echo "🌟 Étape 6: Lancement du serveur de développement..."
echo "Le projet sera accessible sur http://localhost:8000"
echo ""
composer dev

