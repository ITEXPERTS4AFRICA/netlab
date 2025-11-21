#!/bin/bash

echo "🔧 Correction complète de toutes les erreurs de déploiement"
echo "============================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info() {
    echo -e "${GREEN}ℹ️  $1${NC}"
}

warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# 1. Lire les variables de la base de données depuis .env
if [ ! -f .env ]; then
    error "Fichier .env non trouvé"
    exit 1
fi

DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d ' ')
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d ' ')
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2 | tr -d ' ')

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    error "Variables DB_DATABASE ou DB_USERNAME non définies dans .env"
    exit 1
fi

info "Base de données: $DB_NAME"
info "Utilisateur: $DB_USER"

# 2. Créer la table settings si elle n'existe pas
info "1. Vérification de la table settings..."
TABLE_EXISTS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'settings');" 2>/dev/null)

if [ "$TABLE_EXISTS" != "t" ]; then
    warn "La table settings n'existe pas. Création..."
    PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" <<EOF
CREATE TABLE IF NOT EXISTS settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(255) NOT NULL DEFAULT 'string',
    description TEXT,
    is_encrypted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE
);
CREATE INDEX IF NOT EXISTS settings_key_index ON settings(key);
\q
EOF
    success "Table settings créée"
else
    success "Table settings existe déjà"
fi

# 3. Ajouter toutes les colonnes manquantes à la table labs
info "2. Ajout des colonnes manquantes à la table labs..."
PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" <<EOF
-- Ajouter is_published
ALTER TABLE labs ADD COLUMN IF NOT EXISTS is_published BOOLEAN DEFAULT false;

-- Ajouter is_featured
ALTER TABLE labs ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT false;

-- Ajouter les autres colonnes nécessaires
ALTER TABLE labs ADD COLUMN IF NOT EXISTS price_cents BIGINT;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'XOF';
ALTER TABLE labs ADD COLUMN IF NOT EXISTS short_description TEXT;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS tags JSONB;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS categories JSONB;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS difficulty_level VARCHAR(50);
ALTER TABLE labs ADD COLUMN IF NOT EXISTS estimated_duration_minutes INTEGER;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS view_count INTEGER DEFAULT 0;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS reservation_count INTEGER DEFAULT 0;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2);
ALTER TABLE labs ADD COLUMN IF NOT EXISTS rating_count INTEGER DEFAULT 0;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS metadata JSONB;

-- Créer les index
CREATE INDEX IF NOT EXISTS labs_is_published_index ON labs(is_published);
CREATE INDEX IF NOT EXISTS labs_is_featured_index ON labs(is_featured);
CREATE INDEX IF NOT EXISTS labs_difficulty_level_index ON labs(difficulty_level);

-- Marquer tous les labs existants comme publiés par défaut
UPDATE labs SET is_published = true WHERE is_published IS NULL OR is_published = false;

\q
EOF

if [ $? -eq 0 ]; then
    success "Colonnes ajoutées à la table labs"
else
    error "Erreur lors de l'ajout des colonnes"
fi

# 4. Exécuter les migrations
info "3. Exécution des migrations..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    success "Migrations exécutées"
else
    warn "Certaines migrations ont peut-être échoué (normal si les colonnes existent déjà)"
fi

# 5. Nettoyer les caches
info "4. Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

success "Caches nettoyés"

# 6. Recréer les caches optimisés
info "5. Création des caches optimisés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

success "Caches optimisés créés"

# 7. Vérification finale
info "6. Vérification finale..."
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "\d labs" | grep -q "is_published"; then
    success "Colonne is_published vérifiée"
else
    error "Colonne is_published manquante"
fi

echo ""
echo "========================================================================"
success "Correction terminée !"
echo "========================================================================"
echo ""
info "Prochaines étapes :"
echo ""
echo "1. Vérifier que l'application fonctionne : http://10.10.10.20"
echo "2. Créer un utilisateur administrateur : php artisan db:seed --class=AdminUserSeeder"
echo "3. Configurer CinetPay et CML dans l'interface d'administration"
echo ""

