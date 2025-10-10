# NetLab - Laboratoire Virtuel Cisco

## Description

NetLab est une plateforme web moderne développée avec Laravel et React/TypeScript permettant la gestion et la réservation de laboratoires virtuels Cisco. L'application offre une interface intuitive pour la gestion des ressources de laboratoire, les réservations temporelles et l'intégration avec l'API Cisco.

## État d'avancement actuel

### ✅ Fonctionnalités implémentées

#### Backend (Laravel)
- **Modèles de données** :
  - `User` - Gestion des utilisateurs
  - `Lab` - Définition des laboratoires
  - `Reservation` - Système de réservations
  - `Annotations` - Annotations des utilisateurs
  - `Rate` - Système de notation
  - `UsageRecord` - Suivi de l'utilisation

- **Contrôleurs** :
  - `DashboardController` - Gestion du tableau de bord
  - `LabsController` - Gestion des laboratoires
  - `ReservationController` - Gestion des réservations
  - `AnnotationsController` - Gestion des annotations
  - `SmartAnnotationsController` - Annotations intelligentes
  - `LabRuntimeController` - Contrôle d'exécution des labs

- **Services** :
  - `CiscoApiService` - Intégration avec l'API Cisco

- **Migrations** :
  - Tables utilisateurs, laboratoires, réservations, annotations, taux et enregistrements d'utilisation

#### Frontend (React/TypeScript)
- **Pages principales** :
  - `dashboard.tsx` - Page d'accueil avec métriques
  - `welcome.tsx` - Page de bienvenue
  - `labs/Workspace.tsx` - Espace de travail des laboratoires
  - `labs/Labs.tsx` - Liste des laboratoires disponibles
  - `labs/UserReservations.tsx` - Réservations utilisateur
  - `labs/MyReservedLabs.tsx` - Laboratoires réservés par l'utilisateur

- **Composants UI** :
  - Interface complète avec Radix UI
  - Dialogues de réservation (`lab-reservation-dialog.tsx`)
  - Sélecteur de créneaux horaires (`time-slot-picker.tsx`)
  - Annotations (`app-annotation.tsx`)
  - Contrôles de laboratoire (`lab-controls.tsx`)

- **Fonctionnalités avancées** :
  - Système de thèmes (clair/sombre)
  - Design responsive
  - Animations avec Framer Motion
  - Gestion d'état avec React hooks personnalisés

#### Authentification et sécurité
- Système d'authentification complet
- Middleware `EnsureCmlAuthenticated` pour l'API Cisco
- Gestion des sessions utilisateur

### 🔧 Technologies utilisées

#### Backend
- **Laravel 11** - Framework PHP
- **PHP 8.3+** - Langage serveur
- **MySQL/PostgreSQL** - Base de données
- **Inertia.js** - Pont entre Laravel et React

#### Frontend
- **React 19** - Bibliothèque UI
- **TypeScript** - Typage statique
- **Tailwind CSS** - Framework CSS
- **Radix UI** - Composants accessibles
- **Vite** - Outil de build rapide

#### Outils de développement
- **ESLint** - Linting du code
- **Prettier** - Formatage du code
- **PHPUnit** - Tests PHP
- **Vitest** - Tests JavaScript

### 📋 Fonctionnalités en cours de développement

#### Backend
- [ ] API REST complète pour les laboratoires
- [ ] Webhooks pour les événements Cisco
- [ ] Système de notifications en temps réel
- [ ] Export des données d'utilisation

#### Frontend
- [ ] Interface d'administration avancée
- [ ] Graphiques d'utilisation avec Recharts
- [ ] Recherche et filtrage avancés
- [ ] Mode hors ligne

### 🚀 Installation et configuration

#### Prérequis
- PHP 8.3 ou supérieur
- Node.js 18 ou supérieur
- Composer
- MySQL ou PostgreSQL

#### Installation
```bash
# Installation des dépendances PHP
composer install

# Installation des dépendances Node.js
npm install

# Configuration de l'environnement
cp .env.example .env
php artisan key:generate

# Migration de la base de données
php artisan migrate

# Build des assets
npm run build

# Démarrage du serveur de développement
php artisan serve
```

#### Commandes utiles
```bash
# Développement
npm run dev          # Démarrage du serveur Vite
php artisan serve    # Démarrage du serveur Laravel

# Production
npm run build        # Build de production
php artisan optimize # Optimisation Laravel

# Qualité du code
npm run lint         # Linting ESLint
npm run format       # Formatage Prettier
php artisan test     # Tests PHPUnit
```

### 🎯 Prochaines étapes

1. **Finalisation de l'API Cisco**
   - Implémentation complète de l'intégration CML
   - Gestion des états des laboratoires

2. **Amélioration de l'interface utilisateur**
   - Dashboard avec métriques en temps réel
   - Interface d'administration complète

3. **Fonctionnalités avancées**
   - Système de notifications
   - Export de données
   - Rapports d'utilisation

4. **Tests et déploiement**
   - Tests d'intégration complets
   - Documentation API
   - Guide de déploiement

### 📊 Métriques du projet

- **Lignes de code** : ~50,000+ lignes
- **Couverture de tests** : En développement
- **Nombre de composants React** : 25+
- **Contrôleurs Laravel** : 8
- **Modèles de données** : 6
- **Taux de completion** : 75%

### 🤝 Contribution

Le projet suit les bonnes pratiques de développement :
- Code review obligatoire
- Tests automatisés
- Documentation à jour
- Respect des standards PSR-12

### 📝 Notes techniques

- Architecture MVC respectée
- Séparation claire des responsabilités
- Utilisation des services pour la logique métier
- Interface responsive et accessible
- Performance optimisée avec Vite et Laravel Octane (futur)

---

*Dernière mise à jour : Octobre 2025*
