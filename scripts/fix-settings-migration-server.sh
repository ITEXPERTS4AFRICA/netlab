#!/bin/bash

# Script pour résoudre le problème de migration settings sur le serveur
# Usage: ./scripts/fix-settings-migration-server.sh

echo "🔧 Correction de la migration settings sur le serveur"
echo "======================================================="
echo ""

# Lire les variables de la base de données depuis .env
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé"
    exit 1
fi

DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d ' ')
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d ' ')
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2 | tr -d ' ')

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo "❌ Variables DB_DATABASE ou DB_USERNAME non définies dans .env"
    exit 1
fi

echo "📊 Base de données: $DB_NAME"
echo "👤 Utilisateur: $DB_USER"
echo ""

# Vérifier si la table settings existe
echo "1. Vérification de la table settings..."
TABLE_EXISTS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'settings');" 2>/dev/null)

if [ "$TABLE_EXISTS" = "t" ]; then
    echo "   ✅ La table settings existe déjà"
    echo ""
    
    # Vérifier si la migration est enregistrée
    echo "2. Vérification de l'enregistrement de la migration..."
    MIGRATION_EXISTS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT 1 FROM migrations WHERE migration = '2025_11_17_114322_create_settings_table');" 2>/dev/null)
    
    if [ "$MIGRATION_EXISTS" = "t" ]; then
        echo "   ✅ La migration est déjà enregistrée"
        echo ""
        echo "3. Exécution des migrations restantes..."
        php artisan migrate --force
    else
        echo "   ⚠️  La migration n'est pas enregistrée"
        echo ""
        echo "3. Marquage de la migration comme exécutée..."
        
        # Obtenir le batch maximum
        MAX_BATCH=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT COALESCE(MAX(batch), 0) FROM migrations;" 2>/dev/null)
        NEW_BATCH=$((MAX_BATCH + 1))
        
        # Insérer l'enregistrement de migration
        PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" <<EOF
INSERT INTO migrations (migration, batch)
VALUES ('2025_11_17_114322_create_settings_table', $NEW_BATCH)
ON CONFLICT (migration) DO NOTHING;
EOF
        
        if [ $? -eq 0 ]; then
            echo "   ✅ Migration marquée comme exécutée (batch: $NEW_BATCH)"
            echo ""
            echo "4. Exécution des migrations restantes..."
            php artisan migrate --force
        else
            echo "   ❌ Erreur lors du marquage de la migration"
            exit 1
        fi
    fi
else
    echo "   ⚠️  La table settings n'existe pas"
    echo ""
    echo "3. Exécution normale des migrations..."
    php artisan migrate --force
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Toutes les migrations ont été exécutées avec succès !"
else
    echo ""
    echo "❌ Erreur lors de l'exécution des migrations"
    exit 1
fi

