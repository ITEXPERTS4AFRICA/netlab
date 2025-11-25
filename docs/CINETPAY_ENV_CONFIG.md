# Configuration CinetPay dans .env

## Variables requises

Ajoutez ou vérifiez ces variables dans votre fichier `.env` :

```env
# CinetPay Configuration
CINETPAY_API_KEY=votre_api_key_ici
CINETPAY_SITE_ID=votre_site_id_ici
CINETPAY_MODE=production
CINETPAY_API_URL=https://api-checkout.cinetpay.com

# URLs de callback (optionnel, générées automatiquement si non définies)
CINETPAY_NOTIFY_URL=https://votre-domaine.com/api/payments/cinetpay/webhook
CINETPAY_RETURN_URL=https://votre-domaine.com/api/payments/return
CINETPAY_CANCEL_URL=https://votre-domaine.com/api/payments/cancel
```

## Exemple complet (lignes 76-85)

```env
# CinetPay Configuration
CINETPAY_API_KEY=1234567890abcdef1234567890abcdef
CINETPAY_SITE_ID=123456
CINETPAY_MODE=production
CINETPAY_API_URL=https://api-checkout.cinetpay.com
CINETPAY_NOTIFY_URL=
CINETPAY_RETURN_URL=
CINETPAY_CANCEL_URL=
```

## Vérification

### 1. Vérifier que les variables sont définies

```bash
# Sur le serveur
cd /home/allomoh/Documents/netlab
grep CINETPAY .env
```

### 2. Vérifier via Artisan

```bash
php artisan tinker
>>> config('services.cinetpay')
```

### 3. Tester la configuration

```bash
php artisan cinetpay:diagnose-production
```

## URL de l'API

**URL correcte** : `https://api-checkout.cinetpay.com/v2/payment`

- ✅ Le code construit automatiquement l'URL complète : `{api_url}/v2/payment`
- ✅ Si `CINETPAY_API_URL` n'est pas défini, utilise `https://api-checkout.cinetpay.com` par défaut
- ✅ L'URL finale sera : `https://api-checkout.cinetpay.com/v2/payment`

## Mode Production vs Sandbox

### Production
```env
CINETPAY_MODE=production
CINETPAY_API_URL=https://api-checkout.cinetpay.com
```

### Sandbox (développement/test)
```env
CINETPAY_MODE=sandbox
CINETPAY_API_URL=https://api.sandbox.cinetpay.com
```

## Important

1. **Ne pas inclure `/v2/payment` dans `CINETPAY_API_URL`**
   - ❌ `CINETPAY_API_URL=https://api-checkout.cinetpay.com/v2/payment` (INCORRECT)
   - ✅ `CINETPAY_API_URL=https://api-checkout.cinetpay.com` (CORRECT)

2. **L'URL complète est construite automatiquement** dans le code

3. **Après modification du .env**, redémarrer l'application :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Vérification finale

Pour vérifier que tout est correct :

```bash
# 1. Vérifier la configuration
php artisan tinker
>>> $service = new \App\Services\CinetPayService();
>>> // Utiliser la réflexion pour voir l'URL
>>> $reflection = new \ReflectionClass($service);
>>> $apiUrl = $reflection->getProperty('apiUrl');
>>> $apiUrl->setAccessible(true);
>>> echo $apiUrl->getValue($service); // Doit afficher: https://api-checkout.cinetpay.com

# 2. Tester la connexion
php test-cinetpay-production.php
```

## Dépannage

### Problème : Variables non prises en compte

```bash
# Vider le cache de configuration
php artisan config:clear
php artisan cache:clear

# Redémarrer le serveur si nécessaire
```

### Problème : URL incorrecte

Vérifier que `CINETPAY_API_URL` ne contient **pas** `/v2/payment` :
```bash
grep CINETPAY_API_URL .env
# Doit afficher: CINETPAY_API_URL=https://api-checkout.cinetpay.com
# Ne doit PAS contenir: /v2/payment
```

