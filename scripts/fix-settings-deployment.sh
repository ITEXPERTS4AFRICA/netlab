#!/bin/bash

echo "🔧 Correction des problèmes de déploiement Settings et CinetPay"
echo "================================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Fonctions
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

# 1. Vérifier que la base de données existe
info "Vérification de la base de données..."

DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)

if [ -z "$DB_NAME" ]; then
    error "DB_DATABASE non défini dans .env"
    exit 1
fi

if [ -z "$DB_USER" ]; then
    error "DB_USERNAME non défini dans .env"
    exit 1
fi

info "Base de données: $DB_NAME"
info "Utilisateur: $DB_USER"

# 2. Vérifier que la table settings existe
info "Vérification de la table settings..."

TABLE_EXISTS=$(psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'settings');" 2>/dev/null)

if [ "$TABLE_EXISTS" = "t" ]; then
    success "La table settings existe déjà"
else
    warn "La table settings n'existe pas. Création..."
    
    # Créer la table settings
    psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" <<EOF
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
EOF

    if [ $? -eq 0 ]; then
        success "Table settings créée avec succès"
    else
        error "Erreur lors de la création de la table settings"
        exit 1
    fi
fi

# 3. Exécuter les migrations
info "Exécution des migrations..."

php artisan migrate --force

if [ $? -eq 0 ]; then
    success "Migrations exécutées avec succès"
else
    warn "Certaines migrations ont peut-être échoué. Vérification..."
    
    # Vérifier si la migration settings a été créée
    php artisan migrate:status | grep -i settings
    
    if [ $? -ne 0 ]; then
        warn "La migration settings n'a pas été exécutée. Exécution manuelle..."
        php artisan migrate --path=database/migrations/2025_11_17_114322_create_settings_table.php --force
    fi
fi

# 4. Vérifier la configuration CinetPay dans .env
info "Vérification de la configuration CinetPay..."

if grep -q "CINETPAY_API_KEY" .env && grep -q "CINETPAY_SITE_ID" .env; then
    CINETPAY_KEY=$(grep CINETPAY_API_KEY .env | cut -d '=' -f2 | tr -d ' ')
    CINETPAY_SITE=$(grep CINETPAY_SITE_ID .env | cut -d '=' -f2 | tr -d ' ')
    
    if [ -z "$CINETPAY_KEY" ] || [ -z "$CINETPAY_SITE" ]; then
        warn "CinetPay n'est pas configuré dans .env"
        info "Ajoutez ces lignes dans .env :"
        echo "CINETPAY_API_KEY=votre_api_key"
        echo "CINETPAY_SITE_ID=votre_site_id"
        echo "CINETPAY_MODE=sandbox"
    else
        success "Configuration CinetPay trouvée dans .env"
        
        # Optionnel : Synchroniser avec la base de données si la table existe
        if [ "$TABLE_EXISTS" = "t" ]; then
            info "Synchronisation de la configuration CinetPay avec la base de données..."
            php artisan tinker --execute="
                App\Models\Setting::set('cinetpay.api_key', env('CINETPAY_API_KEY', ''), 'string', 'CinetPay API Key', true);
                App\Models\Setting::set('cinetpay.site_id', env('CINETPAY_SITE_ID', ''), 'string', 'CinetPay Site ID');
                App\Models\Setting::set('cinetpay.mode', env('CINETPAY_MODE', 'sandbox'), 'string', 'CinetPay Mode (sandbox/production)');
                echo 'Configuration synchronisée';
            "
        fi
    fi
else
    warn "CinetPay n'est pas configuré dans .env"
fi

# 5. Nettoyer les caches
info "Nettoyage des caches..."

php artisan config:clear
php artisan cache:clear
php artisan view:clear

success "Caches nettoyés"

# 6. Recréer les caches optimisés
info "Création des caches optimisés pour la production..."

php artisan config:cache

success "Configuration mise en cache"

echo ""
echo "========================================================================"
success "Déploiement terminé !"
echo "========================================================================"
echo ""
info "Prochaines étapes :"
echo ""
echo "1. Vérifier que l'application fonctionne : http://10.10.10.20"
echo "2. Configurer CinetPay dans l'interface d'administration (si nécessaire)"
echo "3. Créer un utilisateur administrateur : php artisan db:seed --class=AdminUserSeeder"
echo ""

