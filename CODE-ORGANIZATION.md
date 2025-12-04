# 📁 Organisation du code - NetLab

Ce document décrit l'organisation du code après la réorganisation.

## 🗂️ Structure du projet

```
netlab/
├── app/                    # Code applicatif Laravel
│   ├── Console/           # Commandes Artisan
│   ├── Http/              # Contrôleurs, Middleware, Requests
│   ├── Models/            # Modèles Eloquent
│   ├── Services/          # Services métier (Cisco API, etc.)
│   ├── Traits/            # Traits réutilisables
│   └── Helpers/           # Helpers
│
├── database/              # Migrations, seeders, factories
│   ├── migrations/       # Migrations de base de données
│   ├── seeders/          # Seeders
│   └── factories/        # Factories pour les tests
│
├── docker/                # Configuration Docker
│   ├── nginx/            # Configuration Nginx
│   ├── php/              # Configuration PHP
│   ├── supervisor/       # Configuration Supervisor
│   └── entrypoint.sh     # Script d'initialisation
│
├── docs/                  # Documentation
│   ├── root-docs/        # Documentation déplacée de la racine
│   └── prod/             # Documentation de production
│
├── public/                # Fichiers publics (point d'entrée web)
│
├── resources/             # Ressources frontend
│   ├── js/               # Code TypeScript/React
│   │   ├── components/   # Composants React
│   │   ├── pages/        # Pages Inertia
│   │   ├── hooks/        # Hooks React
│   │   ├── layouts/      # Layouts
│   │   └── utils/        # Utilitaires
│   └── css/              # Styles CSS
│
├── routes/                # Routes Laravel
│   ├── web.php          # Routes web
│   ├── api.php          # Routes API
│   ├── admin.php        # Routes admin
│   └── auth.php         # Routes d'authentification
│
├── scripts/               # Scripts utilitaires
│   ├── tests/           # Scripts de test temporaires
│   ├── maintenance/     # Scripts de maintenance
│   └── utilities/       # Scripts utilitaires
│
├── storage/               # Stockage Laravel (logs, cache, etc.)
│
├── tests/                 # Tests automatisés
│   ├── Feature/         # Tests de fonctionnalités
│   └── Unit/            # Tests unitaires
│
├── Dockerfile            # Image Docker pour PHP/Laravel
├── Dockerfile.node       # Image Docker pour Node.js
├── docker-compose.yml    # Configuration Docker Compose
└── README.md             # Documentation principale
```

## 📋 Règles d'organisation

### Fichiers à la racine

Seuls les fichiers essentiels doivent être à la racine :
- `README.md` - Documentation principale
- `DOCKER.md` - Guide Docker
- `composer.json`, `package.json` - Dépendances
- `Dockerfile*`, `docker-compose.yml` - Configuration Docker
- Fichiers de configuration Laravel standards

### Scripts

- **scripts/tests/** : Scripts de test temporaires (peuvent être supprimés)
- **scripts/maintenance/** : Scripts de maintenance et correction
- **scripts/** : Scripts d'installation et utilitaires généraux

### Documentation

- **docs/** : Toute la documentation du projet
- **docs/root-docs/** : Documentation déplacée de la racine
- **docs/prod/** : Documentation de production

### Tests

- **tests/** : Tests automatisés (Pest/PHPUnit)
- **scripts/tests/** : Scripts de test manuels temporaires

## 🧹 Nettoyage

### Fichiers à supprimer

- Fichiers temporaires (`*.backup`, `*.bak`, `*.tmp`)
- Fichiers de test obsolètes dans `scripts/tests/`
- Fichiers "trouvés" ou autres fichiers temporaires

### Commandes de nettoyage

```bash
# Windows PowerShell
.\scripts\cleanup-temp-files.ps1

# Linux/Mac
./scripts/cleanup-temp-files.sh
```

## 🔄 Réorganisation

Pour réorganiser le code :

```bash
# Windows PowerShell
.\scripts\reorganize-code.ps1

# Linux/Mac
./scripts/reorganize-code.sh
```

## ✅ Checklist de réorganisation

- [x] Fichiers de test déplacés vers `scripts/tests/`
- [x] Documentation de la racine déplacée vers `docs/root-docs/`
- [x] Scripts de maintenance organisés dans `scripts/maintenance/`
- [x] Fichiers temporaires supprimés
- [x] `.gitignore` mis à jour
- [x] Structure Docker organisée dans `docker/`


