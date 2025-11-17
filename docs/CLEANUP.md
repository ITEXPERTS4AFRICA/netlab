# Nettoyage et réorganisation du code

Ce document décrit les changements effectués pour nettoyer et réorganiser le projet.

## 📁 Réorganisation des fichiers

### Scripts shell → `scripts/`
Tous les scripts shell ont été déplacés dans le dossier `scripts/` :
- `install.sh` - Installation des dépendances système
- `setup-postgres.sh` - Configuration PostgreSQL
- `create-db.sh` - Création de la base de données
- `start.sh` - Lancement du projet
- `setup-and-run.sh` - Configuration complète
- `test-and-migrate.sh` - Test et migrations
- `reset-and-migrate.sh` - Réinitialisation de la base
- `fix-php-extensions.sh` - Installation extensions PHP

Un fichier `scripts/README.md` a été créé pour documenter l'utilisation de chaque script.

### Documentation → `docs/`
Tous les fichiers de documentation ont été organisés :
- Fichiers markdown de présentation et documentation
- Images (screenshots, diagrammes)
- Dossier `prod/` avec la documentation technique
- `package-slidev.json` pour les présentations

### Fichiers supprimés

Fichiers temporaires et de backup supprimés :
- `ComposerConfig::disableProcessTimeout` (fichier temporaire)
- `dev`, `npx`, `vite`, `hot` (fichiers temporaires de build)
- `app/Services/CiscoApiService.php.backup` (fichier de backup)

## 📝 Documentation créée

### README principal
Un `README.md` complet a été créé à la racine avec :
- Instructions d'installation
- Structure du projet
- Commandes utiles
- Configuration

### README des scripts
Un `scripts/README.md` documente tous les scripts disponibles avec des exemples d'utilisation.

## 🔧 Améliorations

### .gitignore
Mise à jour pour ignorer les fichiers temporaires :
- Fichiers de build temporaires
- Fichiers de backup
- Fichiers générés automatiquement

## ✅ Résultat

Le projet est maintenant :
- ✅ Mieux organisé (scripts et docs dans leurs dossiers)
- ✅ Plus propre (fichiers temporaires supprimés)
- ✅ Mieux documenté (README complet)
- ✅ Plus maintenable (structure claire)

## 📋 Structure finale

```
netlab/
├── README.md              # Documentation principale
├── scripts/               # Scripts d'installation et configuration
│   ├── README.md         # Documentation des scripts
│   └── *.sh              # Scripts shell
├── docs/                  # Documentation
│   ├── prod/             # Documentation technique
│   ├── *.md              # Fichiers markdown
│   └── *.png, *.jpeg     # Images
├── app/                  # Code applicatif Laravel
├── database/             # Migrations et seeders
├── resources/            # Frontend React/TypeScript
└── routes/               # Routes Laravel
```

