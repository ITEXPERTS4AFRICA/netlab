# Dockerfile pour Laravel + PHP 8.3
FROM php:8.3-fpm-alpine

# Installer les dépendances système
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    postgresql-client \
    sqlite \
    sqlite-dev \
    nginx \
    supervisor \
    bash \
    autoconf \
    g++ \
    make \
    linux-headers

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Installer l'extension Redis via PECL
RUN pecl install redis && \
    docker-php-ext-enable redis

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de configuration
COPY composer.json ./
COPY composer.lock* ./
# Installer toutes les dépendances (y compris dev pour le développement)
RUN if [ -f composer.lock ]; then composer install --optimize-autoloader --no-interaction; else composer install --optimize-autoloader --no-interaction --no-scripts; fi

# Copier le reste de l'application
COPY . .

# Créer les répertoires pour Supervisor
RUN mkdir -p /var/log/supervisor \
    && chmod 755 /var/log/supervisor

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copier la configuration Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copier la configuration PHP-FPM
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Créer les répertoires nécessaires pour Supervisor
RUN mkdir -p /var/log/supervisor /var/run && \
    chmod 755 /var/log/supervisor && \
    chmod 755 /var/run

# Copier la configuration Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exposer le port 80
EXPOSE 80

# Script de démarrage
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

