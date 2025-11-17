# NetLab - Gestion de laboratoires réseau Cisco CML

Application Laravel avec React (Inertia.js) pour la gestion et la réservation de laboratoires réseau Cisco CML.

## 🚀 Démarrage rapide

### Prérequis

- PHP 8.2+
- Composer
- Node.js 20+
- PostgreSQL 12+

### Installation automatique

```bash
# Installation complète (dépendances + PostgreSQL + lancement)
./scripts/setup-and-run.sh
```

### Installation manuelle

1. **Installer les dépendances système**
   ```bash
   ./scripts/install.sh
   ```

2. **Configurer PostgreSQL**
   ```bash
   ./scripts/setup-postgres.sh
   ```

3. **Lancer le projet**
   ```bash
   ./scripts/start.sh
   ```

Le projet sera accessible sur [http://localhost:8000](http://localhost:8000)

## 📁 Structure du projet

```
netlab/
├── app/                    # Code applicatif Laravel
│   ├── Http/              # Contrôleurs, Middleware, Requests
│   ├── Models/            # Modèles Eloquent
│   ├── Services/          # Services métier (Cisco API)
│   └── Console/           # Commandes Artisan
├── database/              # Migrations et seeders
├── resources/             # Frontend React/TypeScript
│   └── js/
│       ├── components/    # Composants React
│       ├── pages/         # Pages Inertia
│       └── layouts/       # Layouts
├── routes/                # Routes Laravel
├── scripts/               # Scripts d'installation et configuration
└── docs/                  # Documentation
```

## 🛠️ Commandes utiles

### Développement

```bash
# Lancer le serveur de développement
composer dev

# Compiler les assets pour la production
npm run build

# Exécuter les tests
composer test
```

### Base de données

```bash
# Exécuter les migrations
php artisan migrate

# Réinitialiser la base de données
./scripts/reset-and-migrate.sh

# Tester la connexion
php artisan db:show
```

### Code quality

```bash
# Formater le code PHP
./vendor/bin/pint

# Formater le code TypeScript/React
npm run format

# Vérifier les types TypeScript
npm run types
```

## 🔧 Configuration

### Variables d'environnement

Le fichier `.env` contient la configuration de l'application. Les variables importantes :

- `DB_CONNECTION=pgsql` - Type de base de données
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - Configuration PostgreSQL
- `CISCO_CML_URL` - URL de l'API Cisco CML
- `CISCO_CML_USERNAME`, `CISCO_CML_PASSWORD` - Credentials Cisco CML

### Services Cisco CML

L'application se connecte à une instance Cisco CML pour gérer les laboratoires réseau. Configurez les credentials dans le fichier `.env`.

## 📚 Documentation

- [Documentation technique](./docs/prod/README.md)
- [Scripts d'installation](./scripts/README.md)
- [Présentation du projet](./docs/presentation.md)

## 🧪 Tests

### Tests généraux

```bash
# Exécuter tous les tests
composer test

# Tests avec couverture
php artisan test --coverage
```

### Tests CML (TDD)

```bash
# Vérifier la connexion CML
./scripts/test-cml-connection.sh

# Tests de connexion de base
php artisan test --filter CmlConnectionTest

# Tests de tous les endpoints CML
php artisan test --filter CmlEndpointsTest

# Tous les tests CML
php artisan test --filter Cml
```

**Configuration requise** : Ajoutez dans votre `.env` :
```env
CML_API_BASE_URL=https://54.38.146.213
CML_USERNAME=votre_username
CML_PASSWORD=votre_password
```

> ✅ L'URL de base CML est déjà configurée. Il ne reste qu'à ajouter vos identifiants.

Voir [Guide TDD](./docs/TDD-GUIDE.md) pour plus de détails.

## 📝 License

MIT

