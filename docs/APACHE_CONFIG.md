# Configuration Apache2 pour NetLab sur Ubuntu

## 1. Installation d'Apache2 et PHP-FPM

```bash
# Installer Apache2
sudo apt install -y apache2

# Installer PHP-FPM (remplacez 8.2 par votre version PHP)
sudo apt install -y php8.2-fpm libapache2-mod-fcgid

# Activer les modules nécessaires
sudo a2enmod rewrite
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.2-fpm

# Redémarrer Apache2
sudo systemctl restart apache2
sudo systemctl enable apache2
```

## 2. Configuration du VirtualHost

Créez le fichier de configuration VirtualHost :

```bash
sudo nano /etc/apache2/sites-available/netlab.conf
```

Contenu du fichier `/etc/apache2/sites-available/netlab.conf` :

```apache
<VirtualHost *:80>
    ServerName netlab.local
    ServerAlias www.netlab.local
    ServerAdmin admin@netlab.local
    
    # Chemin du projet Laravel
    DocumentRoot /home/allomoh/Documents/netlab/public
    
    # Journalisation
    ErrorLog ${APACHE_LOG_DIR}/netlab_error.log
    CustomLog ${APACHE_LOG_DIR}/netlab_access.log combined
    
    # Configuration pour Laravel
    <Directory /home/allomoh/Documents/netlab/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Proxy vers PHP-FPM
        <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://localhost"
        </FilesMatch>
    </Directory>
    
    # Bloquer l'accès aux fichiers sensibles
    <DirectoryMatch "^/.*/\.git/">
        Require all denied
    </DirectoryMatch>
    
    # Blocage des fichiers .env
    <FilesMatch "^\.env">
        Require all denied
    </FilesMatch>
</VirtualHost>
```

**Remplacez :**
- `ServerName` et `ServerAlias` par votre nom de domaine ou IP
- `/home/allomoh/Documents/netlab` par le chemin réel de votre projet
- `php8.2-fpm.sock` par la version PHP que vous utilisez (php8.1-fpm, php8.2-fpm, etc.)

## 3. Activer le site

```bash
# Activer le site
sudo a2ensite netlab.conf

# Désactiver le site par défaut (optionnel)
sudo a2dissite 000-default.conf

# Tester la configuration Apache
sudo apache2ctl configtest

# Redémarrer Apache2
sudo systemctl restart apache2
```

## 4. Configuration des permissions

```bash
# Définir le propriétaire (remplacez 'www-data' par l'utilisateur Apache si différent)
sudo chown -R $USER:www-data /home/allomoh/Documents/netlab

# Permissions pour les dossiers Laravel
sudo chmod -R 755 /home/allomoh/Documents/netlab
sudo chmod -R 775 /home/allomoh/Documents/netlab/storage
sudo chmod -R 775 /home/allomoh/Documents/netlab/bootstrap/cache

# Propriétaire des dossiers de cache et logs
sudo chown -R www-data:www-data /home/allomoh/Documents/netlab/storage
sudo chown -R www-data:www-data /home/allomoh/Documents/netlab/bootstrap/cache
```

## 5. Configuration avec nom de domaine (si vous avez un domaine)

Si vous avez un nom de domaine pointant vers votre serveur, modifiez le VirtualHost :

```apache
<VirtualHost *:80>
    ServerName votre-domaine.com
    ServerAlias www.votre-domaine.com
    # ... reste de la configuration ...
</VirtualHost>
```

## 6. Configuration SSL/HTTPS (optionnel mais recommandé)

```bash
# Installer Certbot
sudo apt install -y certbot python3-certbot-apache

# Générer un certificat SSL
sudo certbot --apache -d votre-domaine.com -d www.votre-domaine.com
```

## 7. Configuration pour accès par IP

Si vous voulez accéder via l'IP du serveur (10.10.10.20), modifiez le VirtualHost :

```apache
<VirtualHost *:80>
    ServerName 10.10.10.20
    ServerAlias netlab.local
    # ... reste de la configuration ...
</VirtualHost>
```

## 8. Vérification

```bash
# Vérifier que Apache2 fonctionne
sudo systemctl status apache2

# Vérifier les logs en cas d'erreur
sudo tail -f /var/log/apache2/netlab_error.log
sudo tail -f /var/log/apache2/netlab_access.log

# Tester depuis le navigateur
# http://10.10.10.20 ou http://votre-domaine.com
```

## 9. Troubleshooting

### Erreur 403 Forbidden
```bash
# Vérifier les permissions
sudo chown -R $USER:www-data /home/allomoh/Documents/netlab
sudo chmod -R 755 /home/allomoh/Documents/netlab/public
```

### Erreur 500 Internal Server Error
```bash
# Vérifier les logs Laravel
tail -f /home/allomoh/Documents/netlab/storage/logs/laravel.log

# Vérifier les permissions storage
sudo chmod -R 775 /home/allomoh/Documents/netlab/storage
sudo chmod -R 775 /home/allomoh/Documents/netlab/bootstrap/cache
```

### PHP ne s'exécute pas
```bash
# Vérifier que PHP-FPM fonctionne
sudo systemctl status php8.2-fpm

# Vérifier le chemin du socket PHP-FPM
ls -la /var/run/php/

# Adapter la configuration Apache avec le bon chemin du socket
```

## 10. Variables d'environnement dans .env

Assurez-vous que votre fichier `.env` contient :

```env
APP_URL=http://10.10.10.20
# ou
APP_URL=http://votre-domaine.com

APP_ENV=production
APP_DEBUG=false
```

## Commandes utiles

```bash
# Redémarrer Apache2
sudo systemctl restart apache2

# Recharger Apache2 (sans interruption)
sudo systemctl reload apache2

# Vérifier la syntaxe de configuration
sudo apache2ctl configtest

# Lister les sites activés
sudo a2ensite
sudo a2dissite

# Voir les modules actifs
apache2ctl -M
```
