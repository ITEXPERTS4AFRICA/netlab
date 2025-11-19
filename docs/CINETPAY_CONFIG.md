# Configuration CinetPay - Guide Complet

## Variables d'environnement requises

Ajoutez ces variables dans votre fichier `.env` :

```env
# Configuration CinetPay - REQUIS
CINETPAY_API_KEY=votre_api_key_ici
CINETPAY_SITE_ID=votre_site_id_ici
CINETPAY_MODE=production

# URLs de callback (optionnel - générées automatiquement si non définies)
# Si vous êtes derrière un proxy ou utilisez un domaine spécifique, définissez-les :
# CINETPAY_NOTIFY_URL=https://votre-domaine.com/api/payments/cinetpay/webhook
# CINETPAY_RETURN_URL=https://votre-domaine.com/api/payments/return
# CINETPAY_CANCEL_URL=https://votre-domaine.com/api/payments/cancel

# URL de l'API (optionnel - par défaut: https://api.cinetpay.com)
# CINETPAY_API_URL=https://api.cinetpay.com
```

## Format correct des variables

⚠️ **IMPORTANT** : Chaque variable doit être sur une ligne séparée dans le fichier `.env`.

❌ **INCORRECT** :
```env
CINETPAY_MODE=productionVITE_HOST=localhost
```

✅ **CORRECT** :
```env
CINETPAY_MODE=production
VITE_HOST=localhost
```

## Modes disponibles

- `production` : Mode production (paiements réels)
- `sandbox` : Mode sandbox/test (paiements de test)
- `test` : Alias pour sandbox

## URLs de callback automatiques

Si vous ne définissez pas les URLs de callback dans `.env`, le système les génère automatiquement en utilisant l'URL de base de votre application :

- **Webhook** : `{APP_URL}/api/payments/cinetpay/webhook`
- **Return** : `{APP_URL}/api/payments/return`
- **Cancel** : `{APP_URL}/api/payments/cancel`

### Configuration pour différents environnements

#### Développement local (localhost)
```env
APP_URL=http://localhost:8003
CINETPAY_MODE=sandbox
# Les URLs seront générées automatiquement : http://localhost:8003/api/payments/...
```

#### Développement réseau local (IP)
```env
APP_URL=http://192.168.50.73:8000
CINETPAY_MODE=sandbox
# Les URLs seront générées automatiquement : http://192.168.50.73:8000/api/payments/...
```

#### Production
```env
APP_URL=https://votre-domaine.com
CINETPAY_MODE=production
# Les URLs seront générées automatiquement : https://votre-domaine.com/api/payments/...
```

## Vérification de la configuration

### 1. Vérifier que les variables sont chargées

```bash
php artisan tinker
>>> config('services.cinetpay')
```

Vous devriez voir :
```php
[
    "api_key" => "votre_api_key",
    "site_id" => "votre_site_id",
    "mode" => "production",
    "notify_url" => "http://...",
    "return_url" => "http://...",
    "cancel_url" => "http://...",
]
```

### 2. Tester la connexion CinetPay

```bash
php artisan cinetpay:test
```

### 3. Vider le cache de configuration

Après avoir modifié `.env`, exécutez :

```bash
php artisan config:clear
php artisan cache:clear
```

## Problèmes courants

### 1. Timeout de connexion

**Symptôme** : `Connection timed out after 45001 milliseconds`

**Solutions** :
- Vérifier votre connexion internet
- Vérifier que l'API CinetPay est accessible : `curl https://api.cinetpay.com`
- En mode sandbox, l'API peut être temporairement indisponible
- Passer en mode production si vous avez les identifiants

### 2. Mode invalide

**Symptôme** : `Mode invalide. Valeurs acceptées: sandbox, production, test`

**Solution** :
- Vérifier que `CINETPAY_MODE` est sur une ligne séparée dans `.env`
- Vérifier qu'il n'y a pas d'espaces : `CINETPAY_MODE=production` (pas `CINETPAY_MODE = production`)
- Exécuter `php artisan config:clear`

### 3. URLs de callback non accessibles

**Symptôme** : CinetPay ne peut pas notifier votre application

**Solutions** :
- Vérifier que `APP_URL` est correctement défini dans `.env`
- Si vous êtes derrière un proxy, définir explicitement les URLs dans `.env`
- Vérifier que les routes sont accessibles publiquement (pas de middleware d'auth sur les webhooks)

### 4. Montant minimum non atteint

**Symptôme** : `Le montant minimum requis est de 100 XOF`

**Solution** :
- CinetPay requiert un minimum de 100 XOF par transaction
- Vérifier que le prix du lab est au moins 100 XOF (10000 centimes)

## Routes de callback

Les routes suivantes doivent être accessibles publiquement (sans authentification) :

- `POST /api/payments/cinetpay/webhook` : Webhook CinetPay pour les notifications
- `GET /api/payments/return` : Page de retour après paiement réussi
- `GET /api/payments/cancel` : Page de retour après annulation

Ces routes sont déjà configurées dans `routes/api.php` sans middleware d'authentification.

## Support

Pour plus d'informations, consultez :
- [Documentation CinetPay](https://doc.cinetpay.com)
- [Documentation de dépannage](./CINETPAY_TIMEOUT_TROUBLESHOOTING.md)

