#!/bin/bash

echo "🔧 Installation des extensions PHP manquantes..."
echo ""

# Détecter la version de PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "Version PHP détectée: $PHP_VERSION"

# Installer les extensions manquantes
echo "📦 Installation de php${PHP_VERSION}-dom et php${PHP_VERSION}-xml..."
sudo apt install -y php${PHP_VERSION}-dom php${PHP_VERSION}-xml

echo ""
echo "✅ Extensions installées !"
echo ""
echo "Vous pouvez maintenant exécuter: composer install"

