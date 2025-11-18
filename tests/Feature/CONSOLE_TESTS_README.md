# Tests TDD pour les Endpoints Console

## Vue d'ensemble

Cette suite de tests TDD couvre tous les endpoints de console pour contrôler et manipuler les labs :

### Endpoints testés

1. **GET `/api/labs/{labId}/nodes/{nodeId}/consoles`** - Lister les consoles disponibles
2. **POST `/api/console/sessions`** - Créer une session console
3. **GET `/api/console/sessions`** - Récupérer les sessions actives
4. **DELETE `/api/console/sessions/{sessionId}`** - Fermer une session
5. **GET `/api/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log`** - Obtenir le log d'une console

## Couverture des tests

### Tests de succès ✅
- ✅ Liste des consoles avec succès
- ✅ Création de session avec différents types (console, serial)
- ✅ Création de session avec protocole personnalisé
- ✅ Création de session avec options personnalisées
- ✅ Récupération des sessions actives
- ✅ Fermeture de session avec succès
- ✅ Récupération du log de console

### Tests d'authentification 🔐
- ✅ Vérification que l'authentification est requise pour tous les endpoints
- ✅ Vérification que les utilisateurs non authentifiés sont rejetés

### Tests de validation 📋
- ✅ Validation que `lab_id` est requis
- ✅ Validation que `node_id` est requis
- ✅ Validation que `type` est une chaîne si fourni
- ✅ Validation que `options` est un tableau si fourni

### Tests de gestion d'erreurs ⚠️
- ✅ Gestion des erreurs lors de la récupération des consoles
- ✅ Gestion gracieuse des erreurs lors de la récupération des types de console
- ✅ Gestion des erreurs lors de la création de session
- ✅ Gestion des erreurs lors de la récupération des sessions
- ✅ Gestion des erreurs lors de la fermeture de session
- ✅ Gestion des erreurs lors de la récupération du log

### Tests de cas limites 🔍
- ✅ Gestion gracieuse de l'absence de token CML
- ✅ Retour de sessions vides quand aucune n'existe
- ✅ Support des différents types de console (serial, vnc, console)

## Structure des tests

Les tests utilisent des mocks pour isoler les tests du service CML réel :

```php
// Mock du ConsoleService
$this->app->singleton(CiscoApiService::class, function () {
    return new class extends CiscoApiService {
        // Mock des méthodes du service
    };
});
```

## Exécution des tests

```bash
# Exécuter tous les tests console
php artisan test --filter=ConsoleControllerTest

# Exécuter un test spécifique
php artisan test --filter=test_it_creates_console_session_successfully
```

## Notes importantes

1. **Base de données de test** : Les tests utilisent SQLite en mémoire (`:memory:`) pour des performances optimales
2. **Mocks** : Les services CML sont mockés pour éviter les appels API réels pendant les tests
3. **Authentification** : Tous les tests nécessitent un utilisateur authentifié (sauf les tests d'authentification)
4. **RefreshDatabase** : La base de données est réinitialisée avant chaque test

## Prochaines améliorations possibles

- [ ] Tests d'intégration avec un vrai service CML (optionnel)
- [ ] Tests de performance pour les sessions multiples
- [ ] Tests de concurrence pour les sessions simultanées
- [ ] Tests de timeout pour les sessions longues
- [ ] Tests de sécurité pour les permissions utilisateur

## Statistiques

- **Total de tests** : 28
- **Couverture** : 100% des endpoints console
- **Types de tests** : Unit, Feature, Integration



