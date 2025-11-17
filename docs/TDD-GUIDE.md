# Guide TDD - Tests Cisco CML

Ce guide explique comment tester la connexion et tous les endpoints CML en mode TDD.

## 📋 Configuration

### 1. Variables d'environnement

Ajoutez dans votre fichier `.env` :

```env
CML_API_BASE_URL=https://votre-serveur-cml.com
CML_USERNAME=votre_username
CML_PASSWORD=votre_password
```

### 2. Vérifier la configuration

```bash
./scripts/test-cml-connection.sh
```

## 🧪 Tests disponibles

### Tests de connexion de base

```bash
php artisan test --filter CmlConnectionTest
```

Ces tests vérifient :
- ✅ Connexion à l'API CML
- ✅ Authentification
- ✅ Vérification du token
- ✅ Récupération des informations système
- ✅ Liste des labs
- ✅ Déconnexion

### Tests de tous les endpoints

```bash
php artisan test --filter CmlEndpointsTest
```

Ces tests vérifient tous les services :
- ✅ **AuthService** : Authentification, authOk, web session timeout
- ✅ **LabService** : Liste des labs, détails d'un lab
- ✅ **NodeService** : Nodes d'un lab, définitions de nodes
- ✅ **LinkService** : Liens d'un lab
- ✅ **SystemService** : Informations système, utilisateurs, devices, health
- ✅ **ImageService** : Définitions d'images
- ✅ **LicensingService** : Statut de licensing
- ✅ **ResourcePoolService** : Resource pools
- ✅ **ConsoleService** : Clés console
- ✅ **GroupService** : Groupes
- ✅ **TelemetryService** : Paramètres de télémétrie

## 🔄 Workflow TDD

### 1. Red - Écrire un test qui échoue

```php
public function test_can_get_lab_details(): void
{
    $this->authenticate();
    
    $lab = $this->cisco->labs->getLab('lab-id');
    
    $this->assertIsArray($lab);
    $this->assertArrayHasKey('id', $lab);
}
```

### 2. Green - Implémenter le minimum pour que le test passe

```php
// Dans LabService.php
public function getLab(string $id): array
{
    return $this->get("/api/v0/labs/{$id}");
}
```

### 3. Refactor - Améliorer le code

- Extraire des méthodes communes
- Améliorer la gestion d'erreurs
- Ajouter de la documentation

## 📝 Structure des tests

### Tests Feature (connexion réelle)

- `tests/Feature/CmlConnectionTest.php` - Tests de connexion de base
- `tests/Feature/CmlEndpointsTest.php` - Tests de tous les endpoints

### Tests Unit (mocks)

- `tests/Unit/CiscoApiServiceTest.php` - Tests unitaires avec mocks

## 🚀 Exécution des tests

### Tous les tests CML

```bash
php artisan test --filter Cml
```

### Un test spécifique

```bash
php artisan test --filter test_can_connect_to_cml_api
```

### Avec couverture

```bash
php artisan test --coverage --filter Cml
```

## 🔍 Dépannage

### Erreur : "CML_API_BASE_URL non configuré"

Vérifiez que votre `.env` contient :
```env
CML_API_BASE_URL=https://votre-serveur-cml.com
```

### Erreur : "Authentification incorrecte"

Vérifiez vos identifiants dans `.env` :
```env
CML_USERNAME=votre_username
CML_PASSWORD=votre_password
```

### Erreur : "Connection refused"

- Vérifiez que le serveur CML est accessible
- Vérifiez l'URL dans `CML_API_BASE_URL`
- Vérifiez les certificats SSL (le code ignore les erreurs SSL avec `verify => false`)

## 📊 Résultats attendus

### Test de connexion réussi

```
✓ can connect to cml api
✓ can verify authentication
✓ can get system information
✓ can get labs list
✓ can logout
```

### Test des endpoints réussi

```
✓ auth service endpoints
✓ lab service endpoints
✓ node service endpoints
✓ link service endpoints
✓ system service endpoints
✓ image service endpoints
✓ licensing service endpoints
✓ resource pool service endpoints
✓ console service endpoints
✓ group service endpoints
✓ telemetry service endpoints
```

## 🎯 Prochaines étapes

1. **Configurer les variables d'environnement**
2. **Exécuter les tests de connexion** : `./scripts/test-cml-connection.sh`
3. **Vérifier que tous les endpoints fonctionnent** : `php artisan test --filter CmlEndpointsTest`
4. **Ajouter de nouveaux tests** pour les endpoints manquants
5. **Implémenter en mode TDD** : Red → Green → Refactor

## 📚 Ressources

- [Documentation des services Cisco](./app/Services/Cisco/README.md)
- [Architecture des services](./docs/prod/README.md)

