# Configuration Cisco CML

## ✅ Configuration actuelle

L'URL de base CML a été configurée dans votre fichier `.env` :

```env
CML_API_BASE_URL=https://54.38.146.213
```

## 📋 Variables d'environnement requises

Pour que la connexion CML fonctionne complètement, ajoutez également dans votre `.env` :

```env
# URL de base de l'API CML
CML_API_BASE_URL=https://54.38.146.213

# Identifiants de connexion CML
CML_USERNAME=votre_username
CML_PASSWORD=votre_password
```

## 🔧 Vérification de la configuration

### 1. Vérifier que l'URL est configurée

```bash
grep CML_API_BASE_URL .env
```

Devrait afficher :
```
CML_API_BASE_URL=https://54.38.146.213
```

### 2. Tester la connexion

Une fois les identifiants ajoutés, testez la connexion :

```bash
./scripts/test-cml-connection.sh
```

Ou directement :

```bash
php artisan test --filter CmlConnectionTest
```

## 📝 Endpoints disponibles

Avec cette URL de base, tous les endpoints CML seront accessibles via :

- Authentification : `https://54.38.146.213/api/v0/auth_extended`
- Labs : `https://54.38.146.213/api/v0/labs`
- Nodes : `https://54.38.146.213/api/v0/labs/{lab_id}/nodes`
- Etc.

## 🔒 Sécurité

⚠️ **Important** : Ne commitez jamais le fichier `.env` avec vos identifiants. Il est déjà dans `.gitignore`.

## 🚀 Prochaines étapes

1. Ajoutez `CML_USERNAME` et `CML_PASSWORD` dans votre `.env`
2. Testez la connexion : `./scripts/test-cml-connection.sh`
3. Vérifiez tous les endpoints : `php artisan test --filter CmlEndpointsTest`

