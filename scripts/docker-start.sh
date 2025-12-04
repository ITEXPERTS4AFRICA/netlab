#!/bin/bash

# Script de démarrage Docker pour NetLab
set -e

echo "🐳 Démarrage de NetLab avec Docker..."

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

# Vérifier si le fichier .env existe
if [ ! -f .env ]; then
    echo "⚠️  Le fichier .env n'existe pas."
    if [ -f .env.example ]; then
        echo "📋 Copie de .env.example vers .env..."
        cp .env.example .env
        echo "✅ Fichier .env créé. Veuillez le configurer avant de continuer."
        echo "   Important: Configurez DB_*, APP_KEY, et les variables CML"
        exit 1
    else
        echo "❌ Aucun fichier .env.example trouvé. Veuillez créer un fichier .env manuellement."
        exit 1
    fi
fi

# Générer la clé d'application si elle n'existe pas
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Génération de la clé d'application..."
    docker-compose run --rm app php artisan key:generate
fi

# Construire et démarrer les services
echo "🏗️  Construction des images Docker..."
docker-compose build

echo "🚀 Démarrage des services..."
docker-compose up -d

# Attendre que les services soient prêts
echo "⏳ Attente que les services soient prêts..."
sleep 10

# Vérifier l'état des services
echo "📊 État des services:"
docker-compose ps

echo ""
echo "✅ NetLab est en cours de démarrage!"
echo ""
echo "📝 Commandes utiles:"
echo "   - Voir les logs: docker-compose logs -f"
echo "   - Arrêter: docker-compose down"
echo "   - Redémarrer: docker-compose restart"
echo ""
echo "🌐 Accès à l'application:"
echo "   - Application: http://localhost:8000"
echo "   - Vite Dev: http://localhost:5173"
echo ""
echo "🔄 Les migrations seront exécutées automatiquement au démarrage."


