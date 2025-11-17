#!/bin/bash

echo "🐘 Création de l'utilisateur et de la base de données PostgreSQL"
echo ""

# Créer l'utilisateur et la base de données
sudo -u postgres psql <<'EOF'
-- Créer l'utilisateur netlab s'il n'existe pas
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_user WHERE usename = 'netlab') THEN
        CREATE USER netlab WITH PASSWORD 'netlab';
        RAISE NOTICE 'Utilisateur netlab créé';
    ELSE
        ALTER USER netlab WITH PASSWORD 'netlab';
        RAISE NOTICE 'Mot de passe de l''utilisateur netlab mis à jour';
    END IF;
END
$$;

-- Créer la base de données si elle n'existe pas
SELECT 'CREATE DATABASE netlab OWNER netlab'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'netlab')\gexec

-- Donner tous les privilèges
GRANT ALL PRIVILEGES ON DATABASE netlab TO netlab;

\q
EOF

if [ $? -eq 0 ]; then
    echo "✅ Utilisateur et base de données créés avec succès"
else
    echo "❌ Erreur lors de la création"
    exit 1
fi

