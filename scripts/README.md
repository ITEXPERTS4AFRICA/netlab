# Scripts d'installation et de configuration

Ce dossier contient les scripts utilitaires pour installer, configurer et lancer le projet NetLab.

## Scripts disponibles

### Installation initiale

- **`install.sh`** - Installe toutes les dépendances système (PHP, Composer, Node.js via nvm)
  ```bash
  ./scripts/install.sh
  ```

### Configuration de la base de données

- **`setup-postgres.sh`** - Configure PostgreSQL (installation, création de l'utilisateur et de la base de données)
  ```bash
  ./scripts/setup-postgres.sh
  ```

- **`create-db.sh`** - Crée uniquement l'utilisateur et la base de données PostgreSQL
  ```bash
  ./scripts/create-db.sh
  ```

### Développement

- **`start.sh`** - Lance le serveur de développement (vérifie les dépendances, installe si nécessaire, puis lance)
  ```bash
  ./scripts/start.sh
  ```

- **`setup-and-run.sh`** - Configuration complète et lancement en une seule commande
  ```bash
  ./scripts/setup-and-run.sh
  ```

### Base de données

- **`test-and-migrate.sh`** - Teste la connexion PostgreSQL et exécute les migrations
  ```bash
  ./scripts/test-and-migrate.sh
  ```

- **`reset-and-migrate.sh`** - Réinitialise la base de données et exécute les migrations
  ```bash
  ./scripts/reset-and-migrate.sh
  ```

### Utilitaires

- **`fix-php-extensions.sh`** - Installe les extensions PHP manquantes (dom, xml)
  ```bash
  ./scripts/fix-php-extensions.sh
  ```

## Ordre d'exécution recommandé

Pour une installation complète :

1. `./scripts/install.sh` - Installer les dépendances système
2. `./scripts/setup-postgres.sh` - Configurer PostgreSQL
3. `./scripts/start.sh` - Lancer le projet

Ou utilisez directement :
```bash
./scripts/setup-and-run.sh
```

