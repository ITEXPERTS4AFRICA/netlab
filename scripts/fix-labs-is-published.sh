#!/bin/bash

echo "🔧 Ajout de la colonne is_published à la table labs"
echo "===================================================="
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

# 2. Vérifier si la colonne existe déjà
info "Vérification de la colonne is_published..."

COLUMN_EXISTS=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'is_published');" 2>/dev/null)

if [ "$COLUMN_EXISTS" = "t" ]; then
    success "La colonne is_published existe déjà"
    exit 0
fi

warn "La colonne is_published n'existe pas. Ajout..."

# 3. Ajouter la colonne et les autres colonnes nécessaires
PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" <<EOF
-- Ajouter is_published si elle n'existe pas
ALTER TABLE labs ADD COLUMN IF NOT EXISTS is_published BOOLEAN DEFAULT false;

-- Ajouter is_featured si elle n'existe pas
ALTER TABLE labs ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT false;

-- Ajouter les autres colonnes nécessaires
ALTER TABLE labs ADD COLUMN IF NOT EXISTS price_cents BIGINT;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'XOF';
ALTER TABLE labs ADD COLUMN IF NOT EXISTS view_count INTEGER DEFAULT 0;
ALTER TABLE labs ADD COLUMN IF NOT EXISTS reservation_count INTEGER DEFAULT 0;

-- Créer les index pour les performances
CREATE INDEX IF NOT EXISTS labs_is_published_index ON labs(is_published);
CREATE INDEX IF NOT EXISTS labs_is_featured_index ON labs(is_featured);

-- Marquer tous les labs existants comme publiés par défaut
UPDATE labs SET is_published = true WHERE is_published IS NULL OR is_published = false;

-- Vérifier
SELECT id, lab_title, is_published, is_featured FROM labs LIMIT 5;
\q
EOF

if [ $? -eq 0 ]; then
    success "Colonne is_published ajoutée avec succès"
    
    # 4. Nettoyer les caches Laravel
    info "Nettoyage des caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    
    success "Caches nettoyés"
    
    echo ""
    info "✅ Correction terminée !"
    info "Les labs existants ont été marqués comme publiés par défaut."
else
    error "Erreur lors de l'ajout de la colonne"
    exit 1
fi

