#!/bin/bash

echo "🔍 Test de la connexion PostgreSQL et exécution des migrations"
echo ""

# Vérifier que le fichier .env existe
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé"
    exit 1
fi

# Vérifier que les variables DB sont configurées
if ! grep -q "DB_CONNECTION=pgsql" .env; then
    echo "⚠️  DB_CONNECTION n'est pas configuré sur pgsql dans .env"
    echo "Mise à jour de la configuration..."
    
    # Mettre à jour .env pour PostgreSQL
    if grep -q "^DB_CONNECTION=" .env; then
        sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    else
        echo "DB_CONNECTION=pgsql" >> .env
    fi
    
    if grep -q "^DB_HOST=" .env; then
        sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    else
        echo "DB_HOST=127.0.0.1" >> .env
    fi
    
    if grep -q "^DB_PORT=" .env; then
        sed -i "s/^DB_PORT=.*/DB_PORT=5432/" .env
    else
        echo "DB_PORT=5432" >> .env
    fi
    
    if grep -q "^DB_DATABASE=" .env; then
        sed -i "s/^DB_DATABASE=.*/DB_DATABASE=netlab/" .env
    else
        echo "DB_DATABASE=netlab" >> .env
    fi
    
    if grep -q "^DB_USERNAME=" .env; then
        sed -i "s/^DB_USERNAME=.*/DB_USERNAME=netlab/" .env
    else
        echo "DB_USERNAME=netlab" >> .env
    fi
    
    if grep -q "^DB_PASSWORD=" .env; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=netlab|" .env
    else
        echo "DB_PASSWORD=netlab" >> .env
    fi
    
    echo "✅ Configuration .env mise à jour"
fi

# Tester la connexion
echo ""
echo "🔍 Test de la connexion à la base de données..."
if php artisan db:show 2>/dev/null; then
    echo "✅ Connexion réussie !"
else
    echo "⚠️  Test de connexion échoué. Vérification manuelle..."
    php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connexion OK';" 2>&1 | head -10
fi

# Exécuter les migrations
echo ""
echo "📦 Exécution des migrations..."
php artisan migrate

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Migrations exécutées avec succès !"
else
    echo ""
    echo "❌ Erreur lors de l'exécution des migrations"
    exit 1
fi

echo ""
echo "✅ Configuration terminée !"

