# Installation NetLab sur Ubuntu - Guide Complet

## 1. Correction du DNS (si nécessaire)

```bash
# Vérifier la configuration DNS
cat /etc/resolv.conf

# Configurer les DNS publics dans systemd-resolved
sudo nano /etc/systemd/resolved.conf
```

Décommentez et modifiez :
```
[Resolve]
DNS=8.8.8.8 8.8.4.4 1.1.1.1
FallbackDNS=1.1.1.1 9.9.9.9
```

Redémarrer le service :
```bash
sudo systemctl restart systemd-resolved
ping -c 2 github.com
```

## 2. Installation de PHP 8.3 (pour compatibilité avec toutes les dépendances)

```bash
# Mettre à jour les paquets
sudo apt update

# Ajouter le dépôt PHP
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Installer PHP 8.3 et extensions nécessaires
sudo apt install -y php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-common \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-bcmath \
    php8.3-sqlite3 \
    php8.3-gd \
    php8.3-intl \
    php8.3-dom \
    php8.3-fileinfo

# Vérifier la version
php -v
```

## 3. Installation de Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
composer --version
```

## 4. Installation de Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

## 5. Installation de PostgreSQL

```bash
sudo apt install -y postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

## 6. Cloner le projet

```bash
cd ~/Documents
git clone https://github.com/ITEXPERTS4AFRICA/netlab.git
cd netlab
```

## 7. Installation des dépendances

### Option A : Installation complète (avec dépendances de développement - nécessite PHP 8.3+)

```bash
composer install
npm install
```

### Option B : Installation production (sans dépendances de développement - fonctionne avec PHP 8.2+)

```bash
# Installer uniquement les dépendances de production
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 8. Configuration de l'application

```bash
# Créer le fichier .env
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Éditer .env avec vos paramètres
nano .env
```

Variables importantes dans `.env` :
```env
APP_NAME=NetLab
APP_ENV=production
APP_DEBUG=false
APP_URL=http://10.10.10.20

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=netlab
DB_USERNAME=netlab
DB_PASSWORD=votre_mot_de_passe

# Configuration CML
CML_API_BASE_URL=https://54.38.146.213
CML_USERNAME=votre_username
CML_PASSWORD=votre_password
```

## 9. Configuration de la base de données PostgreSQL

```bash
# Se connecter à PostgreSQL
sudo -u postgres psql

# Dans PostgreSQL, exécuter :
CREATE DATABASE netlab;
CREATE USER netlab WITH PASSWORD 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON DATABASE netlab TO netlab;
\q

# Tester la connexion
psql -h localhost -U netlab -d netlab
```

## 10. Exécuter les migrations

```bash
php artisan migrate --force
```

## 11. Configuration des permissions

```bash
# Définir le propriétaire
sudo chown -R $USER:www-data ~/Documents/netlab

# Permissions pour Laravel
sudo chmod -R 755 ~/Documents/netlab
sudo chmod -R 775 ~/Documents/netlab/storage
sudo chmod -R 775 ~/Documents/netlab/bootstrap/cache

# Permissions pour Apache
sudo chown -R www-data:www-data ~/Documents/netlab/storage
sudo chown -R www-data:www-data ~/Documents/netlab/bootstrap/cache
```

## 12. Optimisation pour la production

```bash
# Cache de configuration
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Optimiser l'autoloader
composer dump-autoload --optimize
```

## Commandes utiles

```bash
# Vérifier la configuration
php artisan config:show

# Vérifier les routes
php artisan route:list

# Nettoyer les caches
php artisan optimize:clear

# Redémarrer les services
sudo systemctl restart apache2
sudo systemctl restart php8.3-fpm
```
