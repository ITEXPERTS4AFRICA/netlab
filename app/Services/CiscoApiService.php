<?php

namespace App\Services;

use App\Services\Cisco\AuthService;
use App\Services\Cisco\LabService;
use App\Services\Cisco\NodeService;
use App\Services\Cisco\LinkService;
use App\Services\Cisco\SystemService;
use App\Services\Cisco\LicensingService;
use App\Services\Cisco\ImageService;
use App\Services\Cisco\InterfaceService;
use App\Services\Cisco\ResourcePoolService;
use App\Services\Cisco\TelemetryService;
use App\Services\Cisco\GroupService;
use App\Services\Cisco\ImportService;
use App\Services\Cisco\ConsoleService;

/**
 * Façade principale pour l'API Cisco CML
 * Cette classe orchestre tous les services spécialisés
 */
class CiscoApiService
{
    public AuthService $auth;
    public LabService $labs;
    public NodeService $nodes;
    public LinkService $links;
    public SystemService $system;
    public LicensingService $licensing;
    public ImageService $images;
    public InterfaceService $interfaces;
    public ResourcePoolService $resourcePools;
    public TelemetryService $telemetry;
    public GroupService $groups;
    public ImportService $import;
    public ConsoleService $console;

    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // Utiliser la configuration depuis la base de données (Setting) en priorité
        $this->baseUrl = $this->getCmlBaseUrl();
        $this->token = session('cml_token');

        // Initialiser tous les services
        $this->auth = new AuthService();
        $this->labs = new LabService();
        $this->nodes = new NodeService();
        $this->links = new LinkService();
        $this->system = new SystemService();
        $this->licensing = new LicensingService();
        $this->images = new ImageService();
        $this->interfaces = new InterfaceService();
        $this->resourcePools = new ResourcePoolService();
        $this->telemetry = new TelemetryService();
        $this->groups = new GroupService();
        $this->import = new ImportService();
        $this->console = new ConsoleService();

        // Configurer l'URL de base pour tous les services
        if ($this->baseUrl) {
            $this->setBaseUrl($this->baseUrl);
        }
    }

    /**
     * Obtenir l'URL de base CML depuis la configuration
     * Priorité : Base de données (Setting) > config > .env
     */
    protected function getCmlBaseUrl(): ?string
    {
        return \App\Helpers\CmlConfigHelper::getBaseUrl();
    }

    /**
     * Définir l'URL de base pour tous les services
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        // Mettre à jour tous les services via leur méthode publique
        $this->auth->setBaseUrl($this->baseUrl);
        $this->labs->setBaseUrl($this->baseUrl);
        $this->nodes->setBaseUrl($this->baseUrl);
        $this->links->setBaseUrl($this->baseUrl);
        $this->system->setBaseUrl($this->baseUrl);
        $this->licensing->setBaseUrl($this->baseUrl);
        $this->images->setBaseUrl($this->baseUrl);
        $this->interfaces->setBaseUrl($this->baseUrl);
        $this->resourcePools->setBaseUrl($this->baseUrl);
        $this->telemetry->setBaseUrl($this->baseUrl);
        $this->groups->setBaseUrl($this->baseUrl);
        $this->import->setBaseUrl($this->baseUrl);
        $this->console->setBaseUrl($this->baseUrl);
    }

    /**
     * Méthodes de compatibilité pour maintenir l'API existante
     * Ces méthodes délèguent aux services appropriés
     */

    // Auth methods
    public function auth_extended(string $username, string $password): array
    {
        return $this->auth->authExtended($username, $password);
    }

    public function logout($token = null): mixed
    {
        return $this->auth->logout($token);
    }

    public function revokeToken(): void
    {
        $this->auth->revokeToken();
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->auth->setToken($token);
        $this->labs->setToken($token);
        $this->nodes->setToken($token);
        $this->links->setToken($token);
        $this->system->setToken($token);
        $this->licensing->setToken($token);
        $this->images->setToken($token);
        $this->interfaces->setToken($token);
        $this->resourcePools->setToken($token);
        $this->telemetry->setToken($token);
        $this->groups->setToken($token);
        $this->import->setToken($token);
        $this->console->setToken($token);
        session()->put('cml_token', $token);
    }

    // Lab methods - Compatibilité avec l'ancienne API ($token, $id) et nouvelle API ($id)
    public function getLabs($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->labs->setToken($tokenOrNull);
        }
        return $this->labs->getLabs();
    }

    public function getLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            // Ancien format: getLab($token, $id)
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLab($idOrNull);
        }
        // Nouveau format: getLab($id)
        return $this->labs->getLab($tokenOrId);
    }

    public function startLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->startLab($idOrNull);
        }
        return $this->labs->startLab($tokenOrId);
    }

    public function stopLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->stopLab($idOrNull);
        }
        return $this->labs->stopLab($tokenOrId);
    }

    public function wipeLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->wipeLab($idOrNull);
        }
        return $this->labs->wipeLab($tokenOrId);
    }

    public function deleteLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->deleteLab($idOrNull);
        }
        return $this->labs->deleteLab($tokenOrId);
    }

    public function updateLab($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->updateLab($dataOrId, $dataOrNull);
        }
        return $this->labs->updateLab($tokenOrId, $dataOrId);
    }

    public function getLabState($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabState($idOrNull);
        }
        return $this->labs->getLabState($tokenOrId);
    }

    public function getLabSchema($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabSchema($idOrNull);
        }
        return $this->labs->getLabSchema($tokenOrId);
    }

    public function getLabsAnnotation($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabAnnotations($idOrNull);
        }
        return $this->labs->getLabAnnotations($tokenOrId);
    }

    public function getPyatsTestbed($tokenOrId, $idOrNull = null)
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getPyatsTestbed($idOrNull);
        }
        return $this->labs->getPyatsTestbed($tokenOrId);
    }

    public function checkIfConverged($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->checkIfConverged($idOrNull);
        }
        return $this->labs->checkIfConverged($tokenOrId);
    }

    public function createInterfaces($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->createInterfaces($dataOrId, $dataOrNull);
        }
        return $this->labs->createInterfaces($tokenOrId, $dataOrId);
    }

    public function getTopology($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getTopology($idOrNull);
        }
        return $this->labs->getTopology($tokenOrId);
    }

    public function downloadLab($tokenOrId, $idOrNull = null)
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->downloadLab($idOrNull);
        }
        return $this->labs->downloadLab($tokenOrId);
    }

    public function getLabAnnotations($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabAnnotations($idOrNull);
        }
        return $this->labs->getLabAnnotations($tokenOrId);
    }

    public function createLabAnnotation($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->createLabAnnotation($dataOrId, $dataOrNull);
        }
        return $this->labs->createLabAnnotation($tokenOrId, $dataOrId);
    }

    public function updateLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrData, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->updateLabAnnotation($labIdOrAnnotationId, $annotationIdOrData, $dataOrNull);
        }
        return $this->labs->updateLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrData);
    }

    public function deleteLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrNull = null): array
    {
        if ($annotationIdOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->deleteLabAnnotation($labIdOrAnnotationId, $annotationIdOrNull);
        }
        return $this->labs->deleteLabAnnotation($tokenOrId, $labIdOrAnnotationId);
    }

    public function getLabEvents($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabEvents($idOrNull);
        }
        return $this->labs->getLabEvents($tokenOrId);
    }

    // Node methods - Compatibilité avec l'ancienne API
    public function addNode($tokenOrLabId, $dataOrLabId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->addNode($dataOrLabId, $dataOrNull);
        }
        return $this->nodes->addNode($tokenOrLabId, $dataOrLabId);
    }

    public function getLabNodes($tokenOrLabId, $labIdOrNull = null): array
    {
        if ($labIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getLabNodes($labIdOrNull);
        }
        return $this->nodes->getLabNodes($tokenOrLabId);
    }

    public function getNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getNode($labIdOrNodeId, $nodeIdOrNull);
        }
        return $this->nodes->getNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function updateNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrData, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->updateNode($labIdOrNodeId, $nodeIdOrData, $dataOrNull);
        }
        return $this->nodes->updateNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrData);
    }

    public function deleteNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->deleteNode($labIdOrNodeId, $nodeIdOrNull);
        }
        return $this->nodes->deleteNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function startNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->startNode($labIdOrNodeId, $nodeIdOrNull);
        }
        return $this->nodes->startNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function stopNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->stopNode($labIdOrNodeId, $nodeIdOrNull);
        }
        return $this->nodes->stopNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function getNodeState($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getNodeState($labIdOrNodeId, $nodeIdOrNull);
        }
        return $this->nodes->getNodeState($tokenOrLabId, $labIdOrNodeId);
    }

    // System methods - Compatibilité avec l'ancienne API
    public function getUsers($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->system->setToken($tokenOrNull);
        }
        return $this->system->getUsers();
    }

    public function getDevices($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->system->setToken($tokenOrNull);
        }
        return $this->system->getDevices();
    }

    // Link methods
    public function createLink(string $labId, array $data): array
    {
        return $this->links->createLink($labId, $data);
    }

    public function getLabLinks(string $labId): array
    {
        return $this->links->getLabLinks($labId);
    }

    public function getLink(string $labId, string $linkId): array
    {
        return $this->links->getLink($labId, $linkId);
    }

    public function deleteLink(string $labId, string $linkId): array
    {
        return $this->links->deleteLink($labId, $linkId);
    }

    // Interface methods
    public function getInterface(string $labId, string $interfaceId): array
    {
        return $this->interfaces->getInterface($labId, $interfaceId);
    }

    public function updateInterface(string $labId, string $interfaceId, array $data): array
    {
        return $this->interfaces->updateInterface($labId, $interfaceId, $data);
    }

    public function deleteInterface(string $labId, string $interfaceId): array
    {
        return $this->interfaces->deleteInterface($labId, $interfaceId);
    }

    // Image methods
    public function getImageDefinitions(): array
    {
        return $this->images->getImageDefinitions();
    }

    public function uploadImage(array $data): array
    {
        return $this->images->uploadImage($data);
    }

    // Licensing methods
    public function getLicensing(): array
    {
        return $this->licensing->getLicensing();
    }

    /**
     * Obtenir le token actuel
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
}

