#!/bin/bash

echo "🚀 Démarrage de NetLab"
echo "======================"
echo ""

# Aller dans le répertoire du projet
cd "$(dirname "$0")/.." || exit 1

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur: artisan non trouvé. Êtes-vous dans le répertoire du projet ?"
    exit 1
fi

# Vérifier la base de données
echo "📊 Vérification de la base de données..."
if php artisan db:show > /dev/null 2>&1; then
    echo "✅ Base de données accessible"
else
    echo "⚠️  Avertissement: Problème de connexion à la base de données"
fi

# Nettoyer les caches
echo ""
echo "🧹 Nettoyage des caches..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1

# Afficher l'URL
echo ""
echo "✅ Application prête !"
echo ""
echo "🌐 URLs disponibles :"
echo "   - Production (Apache2): http://10.10.10.20"
echo "   - Développement: http://10.10.10.20:8000"
echo ""
echo "📝 Pour lancer le serveur de développement :"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "📝 Pour vérifier Apache2 :"
echo "   sudo systemctl status apache2"
echo ""
