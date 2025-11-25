# Correction de l'URL CinetPay

## 🚨 Problème détecté

Votre fichier `.env` contient :
```
CINETPAY_API_URL=https://api.cinetpay.com
```

Mais l'URL correcte selon la documentation CinetPay est :
```
CINETPAY_API_URL=https://api-checkout.cinetpay.com
```

## ✅ Solution

### Sur le serveur, modifiez le fichier `.env` :

```bash
cd /home/allomoh/Documents/netlab
nano .env
```

**Trouvez la ligne** (probablement autour de la ligne 76-85) :
```env
CINETPAY_API_URL=https://api.cinetpay.com
```

**Remplacez par** :
```env
CINETPAY_API_URL=https://api-checkout.cinetpay.com
```

### Vérification

Après modification, vérifiez :

```bash
# 1. Vérifier que la modification est correcte
grep CINETPAY_API_URL .env
# Doit afficher: CINETPAY_API_URL=https://api-checkout.cinetpay.com

# 2. Vider le cache de configuration
php artisan config:clear
php artisan cache:clear

# 3. Vérifier la configuration
php check-cinetpay-env.php

# 4. Tester la connexion
php artisan cinetpay:diagnose-production
```

## 📋 Configuration complète recommandée

Dans votre `.env`, assurez-vous d'avoir :

```env
# CinetPay Configuration (lignes 76-85)
CINETPAY_API_KEY=votre_api_key
CINETPAY_SITE_ID=votre_site_id
CINETPAY_MODE=production
CINETPAY_API_URL=https://api-checkout.cinetpay.com
CINETPAY_NOTIFY_URL=
CINETPAY_RETURN_URL=
CINETPAY_CANCEL_URL=
```

## ⚠️ Important

1. **Ne pas inclure `/v2/payment` dans l'URL**
   - ❌ `CINETPAY_API_URL=https://api-checkout.cinetpay.com/v2/payment` (INCORRECT)
   - ✅ `CINETPAY_API_URL=https://api-checkout.cinetpay.com` (CORRECT)

2. **L'URL complète est construite automatiquement** : `{api_url}/v2/payment`

3. **Après modification, toujours vider le cache** :
   ```bash
   php artisan config:clear
   ```

## 🔍 Vérification finale

L'URL finale utilisée par l'application sera :
```
https://api-checkout.cinetpay.com/v2/payment
```

C'est exactement l'URL que vous avez mentionnée ! ✅

