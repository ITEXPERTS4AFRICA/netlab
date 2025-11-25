# 🚨 CORRECTION URGENTE : URL CinetPay

## Problème identifié

Les logs montrent que l'URL utilisée est **incorrecte** :
- ❌ URL actuelle : `https://api.cinetpay.com/v2/payment` (retourne 404 HTML)
- ✅ URL correcte : `https://api-checkout.cinetpay.com/v2/payment`

## Solution sur le serveur

### Option 1 : Script automatique (recommandé)

```bash
cd /home/allomoh/Documents/netlab

# 1. Exécuter le script de correction
php fix-cinetpay-url.php

# 2. Modifier le .env manuellement (si le script le demande)
nano .env
# Trouvez la ligne: CINETPAY_API_URL=https://api.cinetpay.com
# Remplacez par: CINETPAY_API_URL=https://api-checkout.cinetpay.com

# 3. Vider le cache
php artisan config:clear
php artisan cache:clear

# 4. Vérifier
php check-cinetpay-logs.php
```

### Option 2 : Correction manuelle

```bash
cd /home/allomoh/Documents/netlab

# 1. Modifier le .env
nano .env

# Trouvez la ligne (probablement ~80):
CINETPAY_API_URL=https://api.cinetpay.com

# Remplacez par:
CINETPAY_API_URL=https://api-checkout.cinetpay.com

# Sauvegarder (Ctrl+O, Enter, Ctrl+X)

# 2. Vider le cache
php artisan config:clear
php artisan cache:clear

# 3. Vérifier la configuration
php check-cinetpay-logs.php
```

## Vérification

Après correction, vérifiez :

```bash
# 1. Vérifier que l'URL est correcte
grep CINETPAY_API_URL .env
# Doit afficher: CINETPAY_API_URL=https://api-checkout.cinetpay.com

# 2. Vérifier la configuration
php artisan tinker
>>> config('services.cinetpay.api_url')
# Doit afficher: "https://api-checkout.cinetpay.com"

# 3. Tester l'endpoint
php test-cinetpay-payment-endpoint.php
```

## Pourquoi ce problème ?

1. Le fichier `.env` contient l'ancienne URL : `https://api.cinetpay.com`
2. Laravel lit d'abord le `.env`, donc même si la base de données est mise à jour, le `.env` prend le dessus
3. L'ancienne URL retourne une page HTML 404 au lieu de JSON

## Après correction

Une fois corrigé, les paiements devraient fonctionner correctement. L'URL utilisée sera :
```
https://api-checkout.cinetpay.com/v2/payment
```

Et l'API retournera du JSON avec le `payment_url` au lieu de HTML 404.

