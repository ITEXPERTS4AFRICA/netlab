#!/bin/bash

set -e  # Arrêter en cas d'erreur

echo "🚀 Installation de l'environnement de production pour NetLab sur Ubuntu"
echo "========================================================================"
echo ""

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
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

# Vérifier que le script est exécuté avec sudo
if [ "$EUID" -ne 0 ]; then 
    error "Veuillez exécuter ce script avec sudo"
    echo "Usage: sudo bash scripts/install-prod.sh"
    exit 1
fi

# Mise à jour des paquets
info "Mise à jour des paquets système..."
apt update
apt upgrade -y

# Installation de PHP 8.2 et extensions
info "Installation de PHP 8.2 et extensions requises..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-common \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-bcmath \
    php8.2-sqlite3 \
    php8.2-gd \
    php8.2-intl \
    php8.2-dom \
    php8.2-fileinfo

success "PHP 8.2 et extensions installées"

# Installation de Composer
info "Installation de Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    
    # Ajouter Composer au PATH global
    if ! grep -q "/usr/local/bin" /etc/environment; then
        echo 'PATH="/usr/local/bin:$PATH"' >> /etc/environment
    fi
    
    success "Composer installé"
else
    success "Composer déjà installé"
fi

# Installation de Node.js 20 LTS
info "Installation de Node.js 20 LTS..."
if ! command -v node &> /dev/null || ! node --version | grep -q "v20"; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
    success "Node.js 20 installé"
else
    success "Node.js déjà installé: $(node --version)"
fi

# Installation de npm global packages utiles
info "Installation des outils Node.js globaux..."
npm install -g pm2 concurrently

# Installation de PostgreSQL
info "Installation de PostgreSQL..."
if ! command -v psql &> /dev/null; then
    apt install -y postgresql postgresql-contrib
    systemctl start postgresql
    systemctl enable postgresql
    success "PostgreSQL installé et démarré"
else
    success "PostgreSQL déjà installé"
fi

# Installation de Nginx (optionnel mais recommandé pour production)
info "Installation de Nginx..."
if ! command -v nginx &> /dev/null; then
    apt install -y nginx
    systemctl start nginx
    systemctl enable nginx
    success "Nginx installé et démarré"
    warn "N'oubliez pas de configurer Nginx pour votre application"
else
    success "Nginx déjà installé"
fi

# Installation d'outils supplémentaires
info "Installation d'outils supplémentaires..."
apt install -y \
    git \
    unzip \
    curl \
    wget \
    supervisor \
    redis-server

# Démarrer et activer Redis
systemctl start redis-server
systemctl enable redis-server
success "Outils supplémentaires installés"

# Configuration des permissions PHP-FPM
info "Configuration des permissions PHP-FPM..."
# S'assurer que www-data peut écrire dans storage et bootstrap/cache
# (sera fait plus tard lors de la configuration de l'application)

# Optimisation PHP pour la production
info "Optimisation de la configuration PHP pour la production..."
PHP_INI="/etc/php/8.2/fpm/php.ini"
PHP_CLI_INI="/etc/php/8.2/cli/php.ini"

# Backup des fichiers de configuration
cp "$PHP_INI" "$PHP_INI.backup"
cp "$PHP_CLI_INI" "$PHP_CLI_INI.backup"

# Optimisations pour production (PHP-FPM)
sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' "$PHP_INI"
sed -i 's/post_max_size = .*/post_max_size = 64M/' "$PHP_INI"
sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
sed -i 's/;opcache.enable=.*/opcache.enable=1/' "$PHP_INI"
sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=128/' "$PHP_INI"
sed -i 's/;opcache.interned_strings_buffer=.*/opcache.interned_strings_buffer=8/' "$PHP_INI"
sed -i 's/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' "$PHP_INI"
sed -i 's/;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' "$PHP_INI"

success "Configuration PHP optimisée pour la production"

# Afficher les versions installées
echo ""
echo "========================================================================"
info "Vérification des installations..."
echo "========================================================================"

php -v | head -n 1
composer --version 2>/dev/null || warn "Composer non trouvé dans PATH (redémarrez le terminal)"
node --version
npm --version
psql --version | head -n 1
nginx -v 2>&1 | head -n 1
redis-server --version | head -n 1

echo ""
echo "========================================================================"
success "Installation de l'environnement terminée !"
echo "========================================================================"
echo ""
info "Prochaines étapes pour configurer l'application :"
echo ""
echo "1. Cloner ou copier votre application dans /var/www/netlab"
echo "2. Configurer PostgreSQL :"
echo "   sudo -u postgres psql"
echo "   CREATE DATABASE netlab;"
echo "   CREATE USER netlab WITH PASSWORD 'netlab';"
echo "   GRANT ALL PRIVILEGES ON DATABASE netlab TO netlab;"
echo ""
echo "3. Installer les dépendances de l'application :"
echo "   cd /var/www/netlab"
echo "   composer install --no-dev --optimize-autoloader"
echo "   npm install"
echo "   npm run build"
echo ""
echo "4. Configurer le fichier .env :"
echo "   cp .env.example .env"
echo "   php artisan key:generate"
echo "   # Éditer .env avec vos paramètres de base de données"
echo ""
echo "5. Exécuter les migrations :"
echo "   php artisan migrate --force"
echo ""
echo "6. Configurer les permissions :"
echo "   sudo chown -R www-data:www-data /var/www/netlab/storage"
echo "   sudo chown -R www-data:www-data /var/www/netlab/bootstrap/cache"
echo "   sudo chmod -R 775 /var/www/netlab/storage"
echo "   sudo chmod -R 775 /var/www/netlab/bootstrap/cache"
echo ""
echo "7. Configurer Nginx pour pointer vers votre application"
echo "8. Redémarrer PHP-FPM : sudo systemctl restart php8.2-fpm"
echo ""
warn "Note: Les configurations de backup PHP sont sauvegardées avec l'extension .backup"
echo ""

