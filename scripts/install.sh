#!/bin/bash

echo "🚀 Installation des dépendances pour le projet Laravel NetLab"
echo ""

# Mise à jour des paquets
echo "📦 Mise à jour des paquets..."
sudo apt update

# Installation de PHP et extensions
echo "🐘 Installation de PHP et extensions..."
sudo apt install -y php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-mysql php8.2-pgsql php8.2-bcmath php8.2-sqlite3

# Installation de Composer
echo "📦 Installation de Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    echo "✅ Composer installé"
else
    echo "✅ Composer déjà installé"
fi

# Installation de Node.js via nvm (version 20 LTS)
echo "📦 Installation de Node.js via nvm..."
if [ ! -d "$HOME/.nvm" ]; then
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    nvm install 20
    nvm use 20
    nvm alias default 20
    echo "✅ Node.js 20 installé via nvm"
else
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    if ! nvm list | grep -q "v20"; then
        nvm install 20
        nvm use 20
        nvm alias default 20
    else
        nvm use 20
    fi
    echo "✅ Node.js 20 configuré"
fi

# Vérification des installations
echo ""
echo "🔍 Vérification des installations..."
if command -v php &> /dev/null; then
    php --version
else
    echo "❌ PHP non trouvé"
fi

if command -v composer &> /dev/null; then
    composer --version
else
    echo "❌ Composer non trouvé"
fi

# Charger nvm pour la vérification
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" 2>/dev/null
if command -v node &> /dev/null; then
    node --version
    npm --version
else
    echo "❌ Node.js non trouvé"
fi

echo ""
echo "✅ Installation terminée !"
echo ""
echo "Prochaines étapes :"
echo "1. cd /home/eureka/Documents/netlab"
echo "2. composer install"
echo "3. npm install"
echo "4. composer dev"

