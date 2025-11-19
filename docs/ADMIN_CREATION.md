# Création d'un Administrateur

Ce document explique comment créer un utilisateur administrateur dans NetLab.

## Méthode 1 : Commande Artisan (Recommandée)

### Création interactive

```bash
php artisan admin:create
```

La commande vous demandera :
- Nom de l'administrateur
- Email de l'administrateur
- Mot de passe (minimum 8 caractères)

### Création avec options

```bash
# Avec toutes les options
php artisan admin:create --name="John Doe" --email="admin@example.com" --password="SecurePassword123"

# Avec options partielles (les autres seront demandées)
php artisan admin:create --email="admin@example.com"

# Forcer la mise à jour si l'utilisateur existe déjà
php artisan admin:create --email="admin@example.com" --password="NewPassword123" --force
```

### Options disponibles

- `--name` : Nom de l'administrateur
- `--email` : Email de l'administrateur (obligatoire)
- `--password` : Mot de passe (minimum 8 caractères)
- `--force` : Forcer la création/mise à jour même si l'utilisateur existe

## Méthode 2 : Seeder Laravel

### Exécuter le seeder AdminUserSeeder

```bash
php artisan db:seed --class=AdminUserSeeder
```

Cela créera automatiquement :
- **admin@netlab.local** / **password** (administrateur principal)
- **admin@example.com** / **admin123** (administrateur de test)

### Exécuter tous les seeders

```bash
php artisan db:seed
```

Cela exécutera :
- `AdminUserSeeder` : Crée les administrateurs
- Utilisateurs de test (student, instructor)

## Utilisateurs créés par défaut

### Administrateurs

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@netlab.local | password | admin |
| admin@example.com | admin123 | admin |

### Utilisateurs de test

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| test@example.com | password | student |
| instructor@example.com | password | instructor |

## Vérifier qu'un utilisateur est admin

### Via le modèle User

```php
$user = User::where('email', 'admin@example.com')->first();

if ($user->isAdmin()) {
    // L'utilisateur est admin
}
```

### Via la base de données

```sql
SELECT id, name, email, role, is_active 
FROM users 
WHERE role = 'admin';
```

## Sécurité

⚠️ **Important** : Changez les mots de passe par défaut en production !

```bash
# Changer le mot de passe d'un admin existant
php artisan admin:create --email="admin@netlab.local" --password="NouveauMotDePasse123" --force
```

## Exemples d'utilisation

### Créer un admin pour le développement

```bash
php artisan admin:create --name="Dev Admin" --email="dev@localhost" --password="dev123456"
```

### Créer un admin pour la production

```bash
php artisan admin:create \
  --name="Administrateur Système" \
  --email="admin@production.com" \
  --password="$(openssl rand -base64 32)"
```

### Réinitialiser un mot de passe admin

```bash
php artisan admin:create --email="admin@example.com" --password="NouveauMotDePasse" --force
```

## Dépannage

### L'utilisateur existe déjà

Utilisez l'option `--force` pour mettre à jour :

```bash
php artisan admin:create --email="admin@example.com" --password="NewPassword" --force
```

### Erreur de validation

Vérifiez que :
- L'email est valide et unique
- Le mot de passe fait au moins 8 caractères
- Le nom n'est pas vide

### Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

