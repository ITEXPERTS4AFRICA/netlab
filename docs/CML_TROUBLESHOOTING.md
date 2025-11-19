# Dépannage CML - Authentification

Ce document explique comment résoudre les problèmes d'authentification avec CML.

## Erreur : "Accès refusé. Identifiants invalides."

### Causes possibles

1. **Identifiants incorrects**
   - Vérifiez que le nom d'utilisateur et le mot de passe sont corrects
   - Assurez-vous qu'il n'y a pas d'espaces avant/après les identifiants
   - Vérifiez la casse (majuscules/minuscules)

2. **URL de base incorrecte**
   - L'URL doit être au format : `https://virl.example.com` ou `http://localhost:8080`
   - Ne pas inclure `/api` à la fin de l'URL
   - Vérifiez que l'URL est accessible depuis votre serveur

3. **Serveur CML inaccessible**
   - Vérifiez que le serveur CML est démarré
   - Testez la connexion avec `curl` :
     ```bash
     curl -k -X POST https://virl.example.com/api/v0/auth_extended \
       -H "Content-Type: application/json" \
       -d '{"username":"votre_user","password":"votre_pass"}'
     ```

4. **Permissions utilisateur**
   - L'utilisateur doit avoir les permissions nécessaires dans CML
   - Vérifiez dans l'interface CML que l'utilisateur existe et est actif

### Vérifications à effectuer

#### 1. Vérifier l'URL de base

L'URL doit être accessible. Testez avec :

```bash
curl -k https://virl.example.com/api/v0/system_information
```

#### 2. Vérifier les identifiants

Testez directement l'authentification :

```bash
curl -k -X POST https://virl.example.com/api/v0/auth_extended \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "username": "votre_username",
    "password": "votre_password"
  }'
```

Si cela fonctionne, vous devriez recevoir un token.

#### 3. Vérifier les logs Laravel

Consultez les logs pour plus de détails :

```bash
tail -f storage/logs/laravel.log
```

Les logs contiennent :
- L'URL utilisée
- Le statut HTTP de la réponse
- Les détails de l'erreur

#### 4. Vérifier la configuration

Dans `/admin/cml-config`, vérifiez que :
- L'URL de base est correcte (sans `/api`)
- Le nom d'utilisateur est correct
- Le mot de passe est correct
- Les identifiants par défaut sont configurés

## Erreur : "Endpoint non trouvé" (404)

### Causes

- L'URL de base contient `/api` à la fin
- L'URL est incorrecte
- Le serveur CML n'est pas accessible

### Solution

1. Vérifiez que l'URL ne contient pas `/api` :
   - ❌ `https://virl.example.com/api`
   - ✅ `https://virl.example.com`

2. Testez l'accessibilité :
   ```bash
   ping virl.example.com
   ```

## Erreur : "Erreur de connexion au serveur CML" (503)

### Causes

- Le serveur CML est arrêté
- Problème de réseau/firewall
- Timeout de connexion

### Solution

1. Vérifiez que le serveur CML est démarré
2. Vérifiez la connectivité réseau
3. Vérifiez les règles de firewall
4. Augmentez le timeout si nécessaire (actuellement 10 secondes)

## Erreur : "Unchecked runtime.lastError: Could not establish connection"

Cette erreur dans la console du navigateur est généralement causée par une extension Chrome qui essaie de se connecter. Ce n'est **pas** une erreur de votre application.

### Solution

- Ignorez cette erreur si l'authentification fonctionne
- Ou désactivez les extensions Chrome pour les tests

## Mode débogage

En mode local (`APP_ENV=local`), des informations supplémentaires sont ajoutées aux réponses d'erreur :

```json
{
  "success": false,
  "message": "Accès refusé. Identifiants invalides ou permissions insuffisantes.",
  "details": {
    "status": 403,
    "url": "https://virl.example.com/api/v0/auth_extended"
  },
  "debug": {
    "base_url": "https://virl.example.com",
    "username": "votre_user",
    "password_provided": true,
    "url_used": "https://virl.example.com/api/v0/auth_extended"
  }
}
```

## Test manuel de connexion

Vous pouvez tester la connexion CML directement depuis le terminal :

```bash
php artisan tinker
```

Puis :

```php
$service = app(\App\Services\CiscoApiService::class);
$service->setBaseUrl('https://virl.example.com');
$result = $service->auth_extended('username', 'password');
print_r($result);
```

## Vérification de la configuration

Vérifiez que la configuration est correctement enregistrée :

```bash
php artisan tinker
```

```php
\App\Models\Setting::get('cml.base_url');
\App\Models\Setting::get('cml.username');
\App\Models\Setting::get('cml.default_username');
```

## Support

Si le problème persiste :

1. Vérifiez les logs Laravel : `storage/logs/laravel.log`
2. Vérifiez les logs du serveur CML
3. Testez la connexion avec `curl` directement
4. Vérifiez la documentation CML pour votre version

