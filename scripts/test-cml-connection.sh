#!/bin/bash

echo "🔍 Test de connexion à l'API Cisco CML"
echo ""

# Vérifier la configuration
if [ -z "$CML_API_BASE_URL" ] && ! grep -q "CML_API_BASE_URL" .env 2>/dev/null; then
    echo "❌ CML_API_BASE_URL non configuré"
    echo ""
    echo "Ajoutez dans votre .env :"
    echo "CML_API_BASE_URL=https://votre-serveur-cml.com"
    echo "CML_USERNAME=votre_username"
    echo "CML_PASSWORD=votre_password"
    exit 1
fi

# Charger les variables d'environnement
if [ -f .env ]; then
    export $(grep -v '^#' .env | grep -E '^CML_' | xargs)
fi

echo "📋 Configuration détectée :"
echo "   URL: ${CML_API_BASE_URL:-non configuré}"
echo "   Username: ${CML_USERNAME:-non configuré}"
echo ""

if [ -z "$CML_API_BASE_URL" ] || [ -z "$CML_USERNAME" ] || [ -z "$CML_PASSWORD" ]; then
    echo "❌ Configuration incomplète"
    exit 1
fi

# Exécuter les tests
echo "🧪 Exécution des tests de connexion..."
echo ""

php artisan test --filter CmlConnectionTest

echo ""
echo "✅ Tests terminés"
echo ""
echo "Pour tester tous les endpoints :"
echo "  php artisan test --filter CmlEndpointsTest"

