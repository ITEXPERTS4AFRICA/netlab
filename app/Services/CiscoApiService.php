<?php

namespace App\Services;

use App\Helpers\CmlConfigHelper;
use App\Services\Cisco\AuthService;
use App\Services\Cisco\ConsoleService;
use App\Services\Cisco\GroupService;
use App\Services\Cisco\ImageService;
use App\Services\Cisco\ImportService;
use App\Services\Cisco\LabService;
use App\Services\Cisco\LicensingService;
use App\Services\Cisco\LinkService;
use App\Services\Cisco\NodeService;
use App\Services\Cisco\ResourcePoolService;
use App\Services\Cisco\SystemService;
use App\Services\Cisco\TelemetryService;
use App\Services\Cisco\InterfaceService;

class CiscoApiService
{
    protected ?string $token = null;
    public AuthService $auth;
    public LabService $labs;
    public NodeService $nodes;
    public LinkService $links;
    public SystemService $system;
    public ImageService $images;
    public LicensingService $licensing;
    public ResourcePoolService $resourcePools;
    public ConsoleService $console;
    public GroupService $groups;
    public TelemetryService $telemetry;
    public ImportService $import;
    public InterfaceService $interfaces;

    // Les credentials par défaut sont maintenant récupérés depuis la base de données via CmlConfigHelper

    public function __construct()
    {
        $baseUrl = CmlConfigHelper::getBaseUrl();

        $this->auth = new AuthService();
        $this->labs = new LabService();
        $this->nodes = new NodeService();
        $this->links = new LinkService();
        $this->system = new SystemService();
        $this->images = new ImageService();
        $this->licensing = new LicensingService();
        $this->resourcePools = new ResourcePoolService();
        $this->console = new ConsoleService();
        $this->groups = new GroupService();
        $this->telemetry = new TelemetryService();
        $this->import = new ImportService();
        $this->interfaces = new InterfaceService();

        if ($baseUrl) {
            $this->auth->setBaseUrl($baseUrl);
            $this->labs->setBaseUrl($baseUrl);
            $this->nodes->setBaseUrl($baseUrl);
            $this->links->setBaseUrl($baseUrl);
            $this->system->setBaseUrl($baseUrl);
            $this->images->setBaseUrl($baseUrl);
            $this->licensing->setBaseUrl($baseUrl);
            $this->resourcePools->setBaseUrl($baseUrl);
            $this->console->setBaseUrl($baseUrl);
            $this->groups->setBaseUrl($baseUrl);
            $this->telemetry->setBaseUrl($baseUrl);
            $this->import->setBaseUrl($baseUrl);
            $this->interfaces->setBaseUrl($baseUrl);
        }

        // Charger le token depuis la session
        $this->token = session('cml_token');

        // S'assurer qu'un token valide est disponible
        $this->ensureValidToken();
    }

    /**
     * S'assure qu'un token CML valide est disponible
     * Si le token est null, s'authentifie automatiquement avec les credentials par défaut
     * Note: On ne vérifie pas la validité du token à chaque appel pour éviter trop de requêtes
     * Le token sera rafraîchi automatiquement si une erreur d'authentification survient
     */
    public function ensureValidToken(): bool
    {
        // Si le token existe déjà, on suppose qu'il est valide
        // Il sera rafraîchi automatiquement si une erreur d'authentification survient
        if ($this->token) {
            return true;
        }

        // Token absent, s'authentifier automatiquement avec les credentials par défaut
        return $this->refreshToken();
    }

    /**
     * Rafraîchit le token CML en utilisant les credentials par défaut configurés dans le back-office
     */
    public function refreshToken(): bool
    {
        try {
            // Obtenir les credentials par défaut depuis la base de données (configurables depuis le back-office)
            $username = CmlConfigHelper::getDefaultUsername();
            $password = CmlConfigHelper::getDefaultPassword();
            $baseUrl = CmlConfigHelper::getBaseUrl();

            if (!$baseUrl) {
                \Log::warning('CML base URL non configurée, impossible de rafraîchir le token');
                return false;
            }

            // S'assurer que le service auth utilise la bonne URL
            $this->auth->setBaseUrl($baseUrl);

            // Authentifier avec les credentials
            $result = $this->auth->authExtended($username, $password);

            if (isset($result['error'])) {
                \Log::error('Échec rafraîchissement token CML', [
                    'error' => $result['error'],
                    'username' => $username,
                ]);
                return false;
            }

            if (isset($result['token'])) {
                $this->setToken($result['token']);
                \Log::info('Token CML rafraîchi automatiquement', [
                    'username' => $username,
                ]);
                return true;
            }

            \Log::warning('Token CML non reçu lors du rafraîchissement');
            return false;
        } catch (\Exception $e) {
            \Log::error('Exception lors du rafraîchissement token CML', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Définir le token CML (centralisé)
     * Le token est stocké dans la session et tous les services le récupèrent automatiquement
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
        // Stocker le token dans la session - tous les services le récupèrent automatiquement
        session()->put('cml_token', $token);
    }

    /**
     * Activer le mode exceptions pour tous les services
     * Utile pour forcer les exceptions au lieu de retourner des arrays avec 'error'
     */
    public function enableExceptions(bool $enable = true): self
    {
        $this->auth->throwExceptions($enable);
        $this->labs->throwExceptions($enable);
        $this->nodes->throwExceptions($enable);
        $this->links->throwExceptions($enable);
        $this->system->throwExceptions($enable);
        $this->images->throwExceptions($enable);
        $this->licensing->throwExceptions($enable);
        $this->resourcePools->throwExceptions($enable);
        $this->console->throwExceptions($enable);
        $this->groups->throwExceptions($enable);
        $this->telemetry->throwExceptions($enable);
        $this->import->throwExceptions($enable);
        $this->interfaces->throwExceptions($enable);
        return $this;
    }

    public function auth_extended(string $username, string $password): array
    {
        return $this->auth->authExtended($username, $password);
    }

    public function getLabs($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->labs->setToken($tokenOrNull);
        } else {
            $this->ensureValidToken();
        }
        return $this->labs->getLabs();
    }

    public function getLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            // Ancien format: getLab($token, $id)
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLab($idOrNull);
        }
        // Nouveau format: getLab($id)
        $this->ensureValidToken();
        return $this->labs->getLab($tokenOrId);
    }

    public function startLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->startLab($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->startLab($tokenOrId);
    }

    public function stopLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->stopLab($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->stopLab($tokenOrId);
    }

    public function wipeLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->wipeLab($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->wipeLab($tokenOrId);
    }

    public function deleteLab($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->deleteLab($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->deleteLab($tokenOrId);
    }

    public function updateLab($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->updateLab($dataOrId, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->updateLab($tokenOrId, $dataOrId);
    }

    public function getLabState($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabState($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getLabState($tokenOrId);
    }

    public function getLabSchema($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabSchema($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getLabSchema($tokenOrId);
    }

    public function getLabsAnnotation($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabAnnotations($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getLabAnnotations($tokenOrId);
    }

    public function getPyatsTestbed($tokenOrId, $idOrNull = null)
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getPyatsTestbed($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getPyatsTestbed($tokenOrId);
    }

    public function checkIfConverged($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->checkIfConverged($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->checkIfConverged($tokenOrId);
    }

    public function createInterfaces($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->createInterfaces($dataOrId, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->createInterfaces($tokenOrId, $dataOrId);
    }

    public function getTopology($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getTopology($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getTopology($tokenOrId);
    }

    public function downloadLab($tokenOrId, $idOrNull = null)
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->downloadLab($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->downloadLab($tokenOrId);
    }

    public function getLabAnnotations($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabAnnotations($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getLabAnnotations($tokenOrId);
    }

    public function createLabAnnotation($tokenOrId, $dataOrId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->createLabAnnotation($dataOrId, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->createLabAnnotation($tokenOrId, $dataOrId);
    }

    public function updateLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrData, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->updateLabAnnotation($labIdOrAnnotationId, $annotationIdOrData, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->updateLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrData);
    }

    public function deleteLabAnnotation($tokenOrId, $labIdOrAnnotationId, $annotationIdOrNull = null): array
    {
        if ($annotationIdOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->deleteLabAnnotation($labIdOrAnnotationId, $annotationIdOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->deleteLabAnnotation($tokenOrId, $labIdOrAnnotationId);
    }

    public function getLabEvents($tokenOrId, $idOrNull = null): array
    {
        if ($idOrNull !== null) {
            if ($tokenOrId === null) {
                $this->ensureValidToken();
                $tokenOrId = $this->token;
            }
            if ($tokenOrId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->labs->setToken($tokenOrId);
            return $this->labs->getLabEvents($idOrNull);
        }
        $this->ensureValidToken();
        return $this->labs->getLabEvents($tokenOrId);
    }

    // Node methods - Compatibilité avec l'ancienne API
    public function addNode($tokenOrLabId, $dataOrLabId, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->addNode($dataOrLabId, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->addNode($tokenOrLabId, $dataOrLabId);
    }

    public function getLabNodes($tokenOrLabId, $labIdOrNull = null): array
    {
        if ($labIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getLabNodes($labIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->getLabNodes($tokenOrLabId);
    }

    public function getNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getNode($labIdOrNodeId, $nodeIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->getNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function updateNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrData, $dataOrNull = null): array
    {
        if ($dataOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->updateNode($labIdOrNodeId, $nodeIdOrData, $dataOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->updateNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrData);
    }

    public function deleteNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->deleteNode($labIdOrNodeId, $nodeIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->deleteNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function startNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->startNode($labIdOrNodeId, $nodeIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->startNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function stopNode($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->stopNode($labIdOrNodeId, $nodeIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->stopNode($tokenOrLabId, $labIdOrNodeId);
    }

    public function getNodeState($tokenOrLabId, $labIdOrNodeId, $nodeIdOrNull = null): array
    {
        if ($nodeIdOrNull !== null) {
            if ($tokenOrLabId === null) {
                $this->ensureValidToken();
                $tokenOrLabId = $this->token;
            }
            if ($tokenOrLabId === null) {
                return ['error' => 'Token CML non disponible. Veuillez vous reconnecter.'];
            }
            $this->nodes->setToken($tokenOrLabId);
            return $this->nodes->getNodeState($labIdOrNodeId, $nodeIdOrNull);
        }
        $this->ensureValidToken();
        return $this->nodes->getNodeState($tokenOrLabId, $labIdOrNodeId);
    }

    // System methods - Compatibilité avec l'ancienne API
    public function getUsers($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->system->setToken($tokenOrNull);
        } else {
            $this->ensureValidToken();
        }
        return $this->system->getUsers();
    }

    public function getDevices($tokenOrNull = null): array
    {
        if ($tokenOrNull) {
            $this->system->setToken($tokenOrNull);
        } else {
            $this->ensureValidToken();
        }
        return $this->system->getDevices();
    }

    // Link methods
    public function createLink(string $labId, array $data): array
    {
        $this->ensureValidToken();
        return $this->links->createLink($labId, $data);
    }

    public function getLabLinks(string $labId): array
    {
        $this->ensureValidToken();
        return $this->links->getLabLinks($labId);
    }

    public function getLink(string $labId, string $linkId): array
    {
        $this->ensureValidToken();
        return $this->links->getLink($labId, $linkId);
    }

    public function deleteLink(string $labId, string $linkId): array
    {
        $this->ensureValidToken();
        return $this->links->deleteLink($labId, $linkId);
    }

    // Interface methods
    public function getInterface(string $labId, string $interfaceId): array
    {
        $this->ensureValidToken();
        return $this->interfaces->getInterface($labId, $interfaceId);
    }

    public function updateInterface(string $labId, string $interfaceId, array $data): array
    {
        $this->ensureValidToken();
        return $this->interfaces->updateInterface($labId, $interfaceId, $data);
    }

    public function deleteInterface(string $labId, string $interfaceId): array
    {
        $this->ensureValidToken();
        return $this->interfaces->deleteInterface($labId, $interfaceId);
    }

    // Image methods
    public function getImageDefinitions(): array
    {
        $this->ensureValidToken();
        return $this->images->getImageDefinitions();
    }

    public function uploadImage(array $data): array
    {
        $this->ensureValidToken();
        return $this->images->uploadImage($data);
    }

    // Licensing methods
    public function getLicensing(): array
    {
        $this->ensureValidToken();
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
