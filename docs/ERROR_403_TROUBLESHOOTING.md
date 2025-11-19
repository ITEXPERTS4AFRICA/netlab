# Dépannage - Erreur 403 (Forbidden)

## Comprendre l'erreur 403

L'erreur **403 Forbidden** signifie que vous n'avez pas les permissions nécessaires pour accéder à cette ressource.

### Causes possibles

1. **Vous n'êtes pas connecté en tant qu'administrateur**
   - Votre compte utilisateur n'a pas le rôle `admin`
   - Votre session a expiré

2. **Session expirée**
   - Les cookies de session ne sont pas envoyés avec la requête
   - La session Laravel a expiré

3. **Problème de cookies**
   - Les cookies ne sont pas inclus dans la requête `fetch`
   - Problème de configuration CORS/SameSite

## Solutions

### 1. Vérifier que vous êtes administrateur

Connectez-vous avec un compte administrateur. Vérifiez votre rôle :

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'votre@email.com')->first();
echo $user->role; // Doit afficher "admin"
```

Si ce n'est pas le cas, créez un admin :

```bash
php artisan admin:create --email="admin@example.com" --password="password123"
```

### 2. Vérifier la session

1. **Rafraîchissez la page** (F5 ou Cmd+R)
2. **Déconnectez-vous et reconnectez-vous**
3. **Vérifiez que vous êtes bien connecté** en regardant le menu utilisateur

### 3. Vérifier les cookies

Ouvrez les outils de développement (F12) et vérifiez :

1. **Onglet Application/Storage > Cookies**
2. Vérifiez que le cookie `laravel_session` est présent
3. Vérifiez que le cookie n'est pas expiré

### 4. Vérifier les logs Laravel

Consultez les logs pour voir pourquoi l'accès est refusé :

```bash
tail -f storage/logs/laravel.log
```

Vous devriez voir des messages comme :
- `Tentative d'accès admin sans authentification` (401)
- `Tentative d'accès admin par un utilisateur non-admin` (403)

### 5. Vérifier la configuration de session

Vérifiez que la configuration de session est correcte dans `.env` :

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

## Erreur "Unchecked runtime.lastError"

Cette erreur dans la console du navigateur est **normale** et peut être ignorée. Elle est causée par une extension Chrome qui essaie de se connecter à un service qui n'existe pas.

**Solution** : Ignorez cette erreur, elle n'affecte pas le fonctionnement de l'application.

## Test rapide

Pour tester si vous avez les droits admin :

```bash
php artisan tinker
```

```php
$user = auth()->user();
if ($user) {
    echo "Utilisateur: {$user->email}\n";
    echo "Rôle: {$user->role}\n";
    echo "Est admin: " . ($user->isAdmin() ? 'Oui' : 'Non') . "\n";
} else {
    echo "Non connecté\n";
}
```

## Si le problème persiste

1. **Videz le cache** :
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

2. **Vérifiez les middlewares** dans `routes/admin.php`

3. **Vérifiez que vous êtes bien connecté** avant d'accéder à `/admin/cml-config`

4. **Créez un nouvel admin** et connectez-vous avec :
   ```bash
   php artisan admin:create --email="admin@test.com" --password="admin123"
   ```

