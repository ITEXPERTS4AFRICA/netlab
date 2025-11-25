# Fix : Timeout CinetPay en Production

## 🚨 Problème

En production, les paiements échouent avec l'erreur `CONNECTION_TIMEOUT` :
```
Le service de paiement est indisponible pour le moment. Veuillez réessayer plus tard.
Code: CONNECTION_TIMEOUT
```

## 🔍 Diagnostic

### Option 1 : Script de diagnostic automatique (recommandé)

Sur le serveur, exécutez :

```bash
cd /home/allomoh/Documents/netlab
php artisan cinetpay:diagnose-production --verbose
```

Cette commande va :
- ✅ Vérifier la configuration
- ✅ Tester la connectivité réseau
- ✅ Tester l'endpoint de signature
- ✅ Analyser les logs récents
- ✅ Proposer des solutions

### Option 2 : Script PHP simple

```bash
cd /home/allomoh/Documents/netlab
php test-cinetpay-production.php
```

Ce script teste rapidement :
- La configuration
- La résolution DNS
- La connectivité HTTP
- L'endpoint de signature

## 🔧 Solutions selon le diagnostic

### Solution 1 : Problème de connectivité réseau

Si le diagnostic montre que le serveur ne peut pas se connecter à CinetPay :

#### Vérifier le firewall
```bash
# Vérifier les règles de firewall
sudo ufw status

# Si nécessaire, autoriser les connexions HTTPS sortantes
sudo ufw allow out 443/tcp
```

#### Tester manuellement
```bash
# Tester la connectivité
curl -v https://api-checkout.cinetpay.com

# Tester la résolution DNS
nslookup api-checkout.cinetpay.com
```

#### Vérifier les proxies
```bash
# Vérifier les variables d'environnement de proxy
env | grep -i proxy

# Si un proxy est configuré, vérifier qu'il fonctionne
```

### Solution 2 : Timeout trop court

Si la connexion fonctionne mais timeout trop rapidement, les timeouts ont été augmentés automatiquement en production (60s au lieu de 45s).

Si le problème persiste, vous pouvez augmenter encore :

Modifier `app/Services/CinetPayService.php` ligne ~146 :
```php
$timeout = app()->environment('production') ? 90 : 45; // Augmenter à 90 secondes
```

### Solution 3 : API CinetPay temporairement indisponible

Si le diagnostic montre que l'API CinetPay ne répond pas :

1. **Vérifier le statut de l'API CinetPay**
   - Contacter le support CinetPay
   - Vérifier leur statut en ligne

2. **Réessayer plus tard**
   - Les APIs peuvent être temporairement indisponibles
   - Réessayer dans quelques minutes/heures

3. **Utiliser le mode sandbox pour tester**
   - Si vous êtes en développement, utiliser le sandbox
   - Vérifier que le mode est correct dans `.env`

### Solution 4 : Problème de configuration

Vérifier la configuration dans `.env` ou la base de données :

```bash
# Vérifier les variables d'environnement
grep CINETPAY .env

# Ou via Artisan
php artisan tinker
>>> config('services.cinetpay')
```

Assurez-vous que :
- `CINETPAY_API_KEY` est défini et valide
- `CINETPAY_SITE_ID` est défini et valide
- `CINETPAY_MODE` est défini (`production` ou `sandbox`)

## 📋 Checklist de diagnostic

Sur le serveur, exécutez ces commandes :

```bash
# 1. Diagnostic complet
php artisan cinetpay:diagnose-production --verbose

# 2. Test rapide
php test-cinetpay-production.php

# 3. Vérifier les logs
tail -n 50 storage/logs/laravel.log | grep -i cinetpay

# 4. Tester la connectivité manuelle
curl -v -X POST https://api-checkout.cinetpay.com/v2/?method=getSignatureByPost \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "apikey=YOUR_API_KEY&cpm_site_id=YOUR_SITE_ID"

# 5. Vérifier le firewall
sudo ufw status

# 6. Vérifier la résolution DNS
nslookup api-checkout.cinetpay.com
```

## 🛠️ Corrections apportées

1. **Timeout augmenté en production** : 60 secondes au lieu de 45
2. **ConnectTimeout ajouté** : 10 secondes pour détecter rapidement les problèmes réseau
3. **Désactivation SSL vérification** : Pour éviter les problèmes de certificat
4. **Meilleure gestion des erreurs** : Logs plus détaillés

## 📝 Logs à vérifier

Les logs Laravel contiennent des informations détaillées :

```bash
# Voir les erreurs CinetPay récentes
tail -f storage/logs/laravel.log | grep -i cinetpay

# Voir les timeouts spécifiquement
tail -f storage/logs/laravel.log | grep -i timeout
```

## 🆘 Si rien ne fonctionne

1. **Contacter le support CinetPay**
   - Vérifier que votre compte est actif
   - Vérifier que les identifiants sont corrects
   - Demander le statut de l'API

2. **Vérifier avec un autre outil**
   - Utiliser Postman ou curl pour tester directement
   - Comparer avec le comportement attendu

3. **Vérifier les logs serveur**
   - Logs Apache/Nginx
   - Logs système (syslog)

## ✅ Vérification finale

Après avoir appliqué les corrections :

1. Exécuter le diagnostic : `php artisan cinetpay:diagnose-production`
2. Tester une réservation depuis l'interface
3. Vérifier les logs pour confirmer que ça fonctionne

Si le problème persiste, partagez les résultats du diagnostic pour une analyse plus approfondie.

