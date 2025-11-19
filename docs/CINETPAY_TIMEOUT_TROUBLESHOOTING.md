# Dépannage - Timeout CinetPay

## Comprendre l'erreur de timeout

L'erreur **"Connection timed out after 45001 milliseconds"** signifie que l'API CinetPay n'a pas répondu dans les 45 secondes allouées.

### Causes possibles

1. **API Sandbox indisponible**
   - Le serveur sandbox de CinetPay peut être temporairement indisponible
   - Les serveurs de test peuvent avoir des problèmes de connectivité

2. **Problème de réseau**
   - Votre connexion internet est lente ou instable
   - Un firewall bloque les connexions vers CinetPay
   - Problème de DNS

3. **Configuration incorrecte**
   - Les identifiants CinetPay sont incorrects
   - L'URL de l'API est incorrecte
   - Le mode (sandbox/production) est mal configuré

## Solutions

### 1. Vérifier la configuration CinetPay

Vérifiez que la configuration est correcte dans `config/services.php` ou `.env` :

```env
CINETPAY_API_KEY=votre_api_key
CINETPAY_SITE_ID=votre_site_id
CINETPAY_MODE=sandbox
```

### 2. Tester la connectivité

Testez si l'API sandbox est accessible :

```bash
curl -X POST https://api.sandbox.cinetpay.com/v2/?method=getSignatureByPost \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "apikey=test&cpm_site_id=test"
```

Si cela timeout aussi, l'API sandbox est probablement indisponible.

### 3. Vérifier les logs

Consultez les logs Laravel pour plus de détails :

```bash
tail -f storage/logs/laravel.log | grep -i cinetpay
```

### 4. Solutions temporaires

#### Option A : Utiliser le mode production (si disponible)

Si vous avez des identifiants de production, changez le mode :

```env
CINETPAY_MODE=production
```

#### Option B : Désactiver temporairement le paiement

Si vous êtes en développement, vous pouvez temporairement désactiver le paiement et créer des réservations gratuites.

#### Option C : Réessayer plus tard

L'API sandbox peut être temporairement indisponible. Réessayez dans quelques minutes.

### 5. Améliorer la gestion des erreurs

Le code a été amélioré pour :
- Détecter spécifiquement les erreurs de timeout
- Afficher des messages d'erreur plus clairs
- Logger plus d'informations pour le débogage

## Messages d'erreur

### En mode sandbox

> "L'API sandbox de CinetPay est temporairement indisponible ou ne répond pas. Le timeout de connexion (45 secondes) a été dépassé. Veuillez réessayer plus tard ou contacter le support si le problème persiste."

### En mode production

> "Timeout de connexion à l'API CinetPay. Le serveur ne répond pas dans les délais impartis. Veuillez réessayer plus tard."

## Vérifications

1. ✅ Les identifiants CinetPay sont-ils corrects ?
2. ✅ Le mode (sandbox/production) est-il correct ?
3. ✅ L'API sandbox est-elle accessible ? (test avec curl)
4. ✅ Votre connexion internet fonctionne-t-elle ?
5. ✅ Les logs Laravel contiennent-ils plus d'informations ?

## Support CinetPay

Si le problème persiste :
- Consultez la documentation CinetPay : https://docs.cinetpay.com
- Contactez le support CinetPay
- Vérifiez le statut de l'API : https://status.cinetpay.com (si disponible)

## Configuration recommandée

Pour le développement, utilisez le mode sandbox avec un timeout plus court (si possible) :

```php
// Dans CinetPayService, le timeout est géré par le SDK (45s)
// Pour réduire le timeout, il faudrait modifier le SDK
```

**Note** : Modifier le SDK n'est pas recommandé. Il vaut mieux attendre que l'API sandbox soit à nouveau disponible.

