# Architecture des Services Cisco CML

## Vue d'ensemble

L'architecture des services Cisco CML a été refactorisée selon les principes SOLID pour améliorer la maintenabilité, la testabilité et la séparation des responsabilités.

## Structure

### Classe de base : `BaseCiscoApiService`
- Fournit les méthodes HTTP de base (GET, POST, PUT, PATCH, DELETE)
- Gère le token d'authentification
- Gère les réponses HTTP et les erreurs

### Services spécialisés

Chaque service est responsable d'un domaine fonctionnel spécifique :

#### 1. **AuthService** - Authentification
```php
use App\Services\Cisco\AuthService;

$auth = new AuthService();
$result = $auth->authExtended($username, $password);
$auth->logout();
$auth->revokeToken();
```

#### 2. **LabService** - Gestion des labs
```php
use App\Services\Cisco\LabService;

$labs = new LabService();
$allLabs = $labs->getLabs();
$lab = $labs->getLab($labId);
$labs->startLab($labId);
$labs->stopLab($labId);
```

#### 3. **NodeService** - Gestion des nodes
```php
use App\Services\Cisco\NodeService;

$nodes = new NodeService();
$labNodes = $nodes->getLabNodes($labId);
$node = $nodes->getNode($labId, $nodeId);
$nodes->startNode($labId, $nodeId);
```

#### 4. **LinkService** - Gestion des liens
```php
use App\Services\Cisco\LinkService;

$links = new LinkService();
$labLinks = $links->getLabLinks($labId);
$links->createLink($labId, $data);
```

#### 5. **InterfaceService** - Gestion des interfaces
```php
use App\Services\Cisco\InterfaceService;

$interfaces = new InterfaceService();
$interface = $interfaces->getInterface($labId, $interfaceId);
```

#### 6. **SystemService** - Configuration système
```php
use App\Services\Cisco\SystemService;

$system = new SystemService();
$users = $system->getUsers();
$devices = $system->getDevices();
```

#### 7. **LicensingService** - Licensing
```php
use App\Services\Cisco\LicensingService;

$licensing = new LicensingService();
$status = $licensing->getLicensingStatus();
```

#### 8. **ImageService** - Gestion des images
```php
use App\Services\Cisco\ImageService;

$images = new ImageService();
$definitions = $images->getImageDefinitions();
```

#### 9. **ResourcePoolService** - Resource pools
```php
use App\Services\Cisco\ResourcePoolService;

$pools = new ResourcePoolService();
$allPools = $pools->getAllResourcePools();
```

#### 10. **TelemetryService** - Télémétrie et diagnostics
```php
use App\Services\Cisco\TelemetryService;

$telemetry = new TelemetryService();
$events = $telemetry->getTelemetryEvents();
```

#### 11. **GroupService** - Gestion des groupes
```php
use App\Services\Cisco\GroupService;

$groups = new GroupService();
$allGroups = $groups->getGroups();
$group = $groups->getGroup($groupId);
$groups->createGroup($data);
```

#### 12. **ImportService** - Import de topologies
```php
use App\Services\Cisco\ImportService;

$import = new ImportService();
$result = $import->importTopology($yamlData);
$result = $import->importVirl1xTopology($virl1xData);
```

#### 13. **ConsoleService** - Console interactive et VNC
```php
use App\Services\Cisco\ConsoleService;

$console = new ConsoleService();

// Obtenir toutes les clés console
$keys = $console->getAllConsoleKeys();

// Accès VNC et console pour un node
$vncKey = $console->getNodeVncKey($labId, $nodeId);
$consoleKey = $console->getNodeConsoleKey($labId, $nodeId);

// URLs d'accès
$vncUrl = $console->getVncUrl($labId, $nodeId, $vncKey);
$consoleUrl = $console->getConsoleUrl($labId, $nodeId, $consoleKey);

// Logs console
$log = $console->getConsoleLog($labId, $nodeId, $consoleId);

// Gestion des sessions console interactives
$sessions = $console->getConsoleSessions();
$session = $console->createConsoleSession($labId, $nodeId);
$console->closeConsoleSession($sessionId);
```

### Façade principale : `CiscoApiService`

La classe `CiscoApiService` agit comme une façade qui orchestre tous les services spécialisés et maintient la compatibilité avec l'ancienne API.

```php
use App\Services\CiscoApiService;

$cisco = new CiscoApiService();

// Accès direct aux services spécialisés (recommandé)
$cisco->labs->getLabs();
$cisco->nodes->getNode($labId, $nodeId);
$cisco->auth->authExtended($username, $password);
$cisco->groups->getGroups();
$cisco->import->importTopology($data);
$cisco->console->getNodeVncKey($labId, $nodeId);

// Ou via les méthodes de compatibilité
$cisco->getLabs();
$cisco->getNode($labId, $nodeId);
```

## Migration depuis l'ancienne API

### Ancienne API (avec token explicite)
```php
$cisco->getLabs($token);
$cisco->getLab($token, $labId);
$cisco->startLab($token, $labId);
```

### Nouvelle API (recommandée - token en session)
```php
// Le token est automatiquement récupéré depuis la session
$cisco->labs->getLabs();
$cisco->labs->getLab($labId);
$cisco->labs->startLab($labId);

// Ou via la façade (compatible)
$cisco->getLabs();
$cisco->getLab($labId);
$cisco->startLab($labId);
```

### Compatibilité rétroactive

La façade `CiscoApiService` maintient la compatibilité avec l'ancienne API. Les deux formats fonctionnent :

```php
// Ancien format (toujours supporté)
$cisco->getLab($token, $labId);

// Nouveau format (recommandé)
$cisco->getLab($labId);
// ou
$cisco->labs->getLab($labId);
```

## Avantages de la nouvelle architecture

### 1. **Single Responsibility Principle (SRP)**
Chaque service a une seule responsabilité :
- `AuthService` : authentification uniquement
- `LabService` : gestion des labs uniquement
- etc.

### 2. **Maintenabilité améliorée**
- Code organisé par domaine fonctionnel
- Fichiers plus petits et faciles à naviguer
- Réduction de la complexité

### 3. **Testabilité**
- Services isolés faciles à tester
- Dépendances claires
- Mocking simplifié

### 4. **Réutilisabilité**
- Services peuvent être utilisés indépendamment
- Composition flexible

### 5. **Extensibilité**
- Facile d'ajouter de nouveaux services
- Modifications localisées

## Gestion du token

### Automatique (recommandé)
```php
// Le token est automatiquement récupéré depuis la session
$cisco->labs->getLabs();
```

### Manuel (si nécessaire)
```php
// Définir le token pour tous les services
$cisco->setToken($token);

// Ou définir le token pour un service spécifique
$cisco->labs->setToken($token);
```

## Exemple d'utilisation dans un contrôleur

```php
use App\Services\CiscoApiService;

class LabController extends Controller
{
    public function index(CiscoApiService $cisco)
    {
        // Utilisation directe des services (recommandé)
        $labs = $cisco->labs->getLabs();
        
        // Ou via la façade
        $labs = $cisco->getLabs();
        
        return view('labs.index', compact('labs'));
    }
    
    public function show(CiscoApiService $cisco, $id)
    {
        $lab = $cisco->labs->getLab($id);
        $nodes = $cisco->nodes->getLabNodes($id);
        $links = $cisco->links->getLabLinks($id);
        
        return view('labs.show', compact('lab', 'nodes', 'links'));
    }
}
```

## Tests

Exemple de test unitaire avec la nouvelle architecture :

```php
use App\Services\Cisco\LabService;
use Tests\TestCase;

class LabServiceTest extends TestCase
{
    public function test_can_get_labs()
    {
        $labService = new LabService();
        $labService->setToken('fake-token');
        
        $labs = $labService->getLabs();
        
        $this->assertIsArray($labs);
    }
}
```

## Fichiers de l'architecture

```
app/Services/
├── Cisco/
│   ├── BaseCiscoApiService.php      # Classe de base
│   ├── AuthService.php               # Authentification
│   ├── LabService.php                # Labs
│   ├── NodeService.php               # Nodes
│   ├── LinkService.php               # Links
│   ├── InterfaceService.php          # Interfaces
│   ├── SystemService.php             # Système
│   ├── LicensingService.php          # Licensing
│   ├── ImageService.php              # Images
│   ├── ResourcePoolService.php       # Resource pools
│   ├── TelemetryService.php          # Télémétrie
│   ├── GroupService.php              # Groupes
│   ├── ImportService.php             # Import de topologies
│   └── README.md                     # Ce fichier
└── CiscoApiService.php               # Façade principale
```

## Questions fréquentes

### Q: Dois-je migrer tout mon code vers la nouvelle API ?
R: Non, la façade maintient la compatibilité rétroactive. Vous pouvez migrer progressivement.

### Q: Comment accéder à un service spécifique ?
R: Via la façade : `$cisco->labs`, `$cisco->nodes`, etc.

### Q: Le token est-il géré automatiquement ?
R: Oui, il est récupéré depuis la session. Vous pouvez le définir manuellement si nécessaire avec `setToken()`.

### Q: Puis-je utiliser les services indépendamment ?
R: Oui, chaque service peut être instancié et utilisé seul.

### Q: L'ancienne API fonctionne-t-elle toujours ?
R: Oui, toutes les anciennes méthodes avec `$token` comme premier paramètre fonctionnent encore.

