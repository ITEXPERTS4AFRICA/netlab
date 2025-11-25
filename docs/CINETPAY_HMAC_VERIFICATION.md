# Vérification du token HMAC CinetPay

## Vue d'ensemble

Le système de vérification du token HMAC a été implémenté pour sécuriser les webhooks CinetPay. Selon la [documentation officielle CinetPay](https://docs.cinetpay.com/api/1.0-fr/checkout/hmac), chaque notification de paiement contient un token HMAC dans l'en-tête `x-token` qui doit être vérifié pour garantir l'authenticité de la requête.

## Fonctionnement

### 1. Réception du webhook

Lorsqu'un paiement change de statut, CinetPay envoie une requête POST sur l'URL de notification configurée (`/api/payments/cinetpay/webhook`) avec :

**En-tête :**
- `x-token` : Le token HMAC à vérifier

**Corps de la requête :**
- `cpm_site_id` : L'ID du site
- `cpm_trans_id` : L'ID de la transaction
- `cpm_trans_date` : La date et heure de la transaction
- `cpm_amount` : Le montant
- `cpm_currency` : La devise
- `signature` : Un token (différent du token HMAC)
- `payment_method` : La méthode de paiement
- `cel_phone_num` : Le numéro de téléphone
- `cpm_phone_prefixe` : Le préfixe du pays
- `cpm_language` : La langue utilisée
- `cpm_version` : La version (V4)
- `cpm_payment_config` : Le type de paiement (Single)
- `cpm_page_action` : Le type d'action (Payment)
- `cpm_custom` : Les métadonnées personnalisées
- `cpm_designation` : La désignation
- `cpm_error_message` : Le statut de la transaction

### 2. Vérification du token HMAC

Le système calcule le HMAC SHA256 en utilisant :
- **Données** : La concaténation des valeurs des champs dans un ordre spécifique
- **Clé secrète** : L'API Key CinetPay (`CINETPAY_API_KEY`)

Le token calculé est comparé avec le token reçu dans l'en-tête `x-token` en utilisant `hash_equals()` pour éviter les attaques par timing.

### 3. Méthodes de vérification

Deux méthodes sont disponibles :

#### Méthode standard (`verifyWebhookHmacToken`)
Utilise les champs spécifiques dans l'ordre défini par la documentation CinetPay.

#### Méthode flexible (`verifyWebhookHmacTokenFlexible`)
Utilise tous les champs présents dans les données du webhook (triés par ordre alphabétique). Cette méthode est utilisée en fallback si la méthode standard échoue.

## Implémentation

### Dans le webhook (`PaymentController::webhook`)

```php
// Récupérer le token depuis l'en-tête
$receivedToken = $request->header('x-token') ?? $request->header('X-TOKEN');

// Vérifier que le token est présent
if (empty($receivedToken)) {
    return response()->json(['error' => 'Token HMAC manquant'], 401);
}

// Vérifier le token HMAC
$isValid = $this->cinetPayService->verifyWebhookHmacToken($data, $receivedToken);

// Si la vérification standard échoue, essayer la méthode flexible
if (!$isValid) {
    $isValid = $this->cinetPayService->verifyWebhookHmacTokenFlexible($data, $receivedToken);
}

if (!$isValid) {
    return response()->json(['error' => 'Token HMAC invalide'], 401);
}
```

### Dans le service (`CinetPayService`)

```php
public function verifyWebhookHmacToken(array $webhookData, string $receivedToken): bool
{
    // Construire la chaîne de données selon l'ordre spécifique
    $fields = [
        'cpm_site_id',
        'cpm_trans_id',
        'cpm_trans_date',
        'cpm_amount',
        'cpm_currency',
        'signature',
        'payment_method',
        'cel_phone_num',
        'cpm_phone_prefixe',
        'cpm_language',
        'cpm_version',
        'cpm_payment_config',
        'cpm_page_action',
        'cpm_custom',
        'cpm_designation',
        'cpm_error_message',
    ];

    $dataString = '';
    foreach ($fields as $field) {
        $value = $webhookData[$field] ?? '';
        $dataString .= (string)$value;
    }

    // Calculer le HMAC SHA256
    $calculatedToken = hash_hmac('sha256', $dataString, $this->apiKey);

    // Comparer de manière sécurisée
    return hash_equals($calculatedToken, $receivedToken);
}
```

## Sécurité

### Protection contre les attaques

1. **Comparaison sécurisée** : Utilisation de `hash_equals()` pour éviter les attaques par timing
2. **Validation stricte** : Rejet des webhooks sans token HMAC
3. **Logging** : Toutes les tentatives de vérification sont loggées pour audit
4. **Double vérification** : Méthode standard + méthode flexible en fallback

### Logs

Les logs incluent :
- Statut de la vérification (valide/invalide)
- Aperçu des tokens (premiers 20 caractères)
- Longueur de la chaîne de données
- Erreurs éventuelles

## Configuration

Aucune configuration supplémentaire n'est nécessaire. Le système utilise :
- `CINETPAY_API_KEY` : La clé API pour calculer le HMAC
- `CINETPAY_NOTIFY_URL` : L'URL du webhook (par défaut : `/api/payments/cinetpay/webhook`)

## Tests

Pour tester la vérification HMAC :

1. **Webhook valide** : CinetPay enverra automatiquement un webhook avec un token HMAC valide lors d'un changement de statut de paiement.

2. **Webhook invalide** : Toute requête sans token HMAC ou avec un token invalide sera rejetée avec une erreur 401.

3. **Logs** : Vérifier les logs Laravel pour voir les détails de la vérification :
   ```bash
   tail -f storage/logs/laravel.log | grep "CinetPay HMAC"
   ```

## Dépannage

### Le token HMAC est toujours invalide

1. **Vérifier l'API Key** : S'assurer que `CINETPAY_API_KEY` est correcte
2. **Vérifier les données** : S'assurer que tous les champs requis sont présents
3. **Vérifier l'ordre** : L'ordre des champs est important pour le calcul
4. **Consulter les logs** : Les logs contiennent des détails sur le calcul

### Le webhook est rejeté

1. **Vérifier l'en-tête** : S'assurer que CinetPay envoie bien l'en-tête `x-token`
2. **Vérifier la configuration** : S'assurer que l'URL du webhook est correcte dans CinetPay
3. **Vérifier les logs** : Consulter les logs pour voir la raison du rejet

## Références

- [Documentation CinetPay - HMAC](https://docs.cinetpay.com/api/1.0-fr/checkout/hmac)
- [Documentation CinetPay - Webhooks](https://docs.cinetpay.com/api/1.0-fr/checkout/webhooks)

