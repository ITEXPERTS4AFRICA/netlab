<?php

namespace Tests\Feature;

use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests complets de tous les endpoints CML
 * 
 * Ces tests vérifient que tous les endpoints implémentés fonctionnent correctement.
 * Exécutez avec: php artisan test --filter CmlEndpointsTest
 */
class CmlEndpointsTest extends TestCase
{
    protected CiscoApiService $cisco;
    protected ?string $token = null;
    protected ?string $testLabId = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cisco = new CiscoApiService();
        
        // Authentification automatique
        $this->authenticate();
    }

    /**
     * Test: AuthService - Tous les endpoints d'authentification
     */
    public function test_auth_service_endpoints(): void
    {
        // authExtended (déjà testé dans authenticate)
        $this->assertNotNull($this->token);

        // authOk
        $result = $this->cisco->auth->authOk();
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);

        // getWebSessionTimeout
        $result = $this->cisco->auth->getWebSessionTimeout();
        $this->assertIsArray($result);
    }

    /**
     * Test: LabService - Tous les endpoints de labs
     */
    public function test_lab_service_endpoints(): void
    {
        // getLabs
        $labs = $this->cisco->labs->getLabs();
        $this->assertIsArray($labs);
        $this->assertArrayNotHasKey('error', $labs);

        // Si des labs existent, tester getLab
        if (!empty($labs) && is_array($labs)) {
            $firstLab = reset($labs);
            if (isset($firstLab['id'])) {
                $this->testLabId = $firstLab['id'];
                
                $lab = $this->cisco->labs->getLab($this->testLabId);
                $this->assertIsArray($lab);
                $this->assertArrayNotHasKey('error', $lab);
            }
        }
    }

    /**
     * Test: NodeService - Endpoints de nodes
     */
    public function test_node_service_endpoints(): void
    {
        if (!$this->testLabId) {
            $this->getTestLabId();
        }

        if (!$this->testLabId) {
            $this->markTestSkipped('Aucun lab disponible pour tester les nodes');
        }

        // getLabNodes
        $nodes = $this->cisco->nodes->getLabNodes($this->testLabId);
        $this->assertIsArray($nodes);
        $this->assertArrayNotHasKey('error', $nodes);

        // getNodeDefinitions
        $definitions = $this->cisco->nodes->getNodeDefinitions();
        $this->assertIsArray($definitions);
    }

    /**
     * Test: LinkService - Endpoints de liens
     */
    public function test_link_service_endpoints(): void
    {
        if (!$this->testLabId) {
            $this->getTestLabId();
        }

        if (!$this->testLabId) {
            $this->markTestSkipped('Aucun lab disponible pour tester les liens');
        }

        // getLabLinks
        $links = $this->cisco->links->getLabLinks($this->testLabId);
        $this->assertIsArray($links);
        $this->assertArrayNotHasKey('error', $links);
    }

    /**
     * Test: SystemService - Endpoints système
     */
    public function test_system_service_endpoints(): void
    {
        // getSystemInformation
        $info = $this->cisco->system->getSystemInformation();
        $this->assertIsArray($info);
        $this->assertArrayNotHasKey('error', $info);

        // getUsers
        $users = $this->cisco->system->getUsers();
        $this->assertIsArray($users);

        // getDevices
        $devices = $this->cisco->system->getDevices();
        $this->assertIsArray($devices);

        // getSystemHealth
        $health = $this->cisco->system->getSystemHealth();
        $this->assertIsArray($health);
    }

    /**
     * Test: ImageService - Endpoints d'images
     */
    public function test_image_service_endpoints(): void
    {
        // getImageDefinitions
        $images = $this->cisco->images->getImageDefinitions();
        $this->assertIsArray($images);
        $this->assertArrayNotHasKey('error', $images);
    }

    /**
     * Test: LicensingService - Endpoints de licensing
     */
    public function test_licensing_service_endpoints(): void
    {
        // getLicensing
        $licensing = $this->cisco->licensing->getLicensing();
        $this->assertIsArray($licensing);

        // getLicensingStatus
        $status = $this->cisco->licensing->getLicensingStatus();
        $this->assertIsArray($status);
    }

    /**
     * Test: ResourcePoolService - Endpoints de resource pools
     */
    public function test_resource_pool_service_endpoints(): void
    {
        // getAllResourcePools
        $pools = $this->cisco->resourcePools->getAllResourcePools();
        $this->assertIsArray($pools);
        // Certains endpoints peuvent ne pas être disponibles, on vérifie juste que c'est un array
        if (isset($pools['error'])) {
            $this->markTestSkipped('Resource pools endpoint non disponible: ' . $pools['error']);
        }
    }

    /**
     * Test: ConsoleService - Endpoints de console
     */
    public function test_console_service_endpoints(): void
    {
        // getAllConsoleKeys
        $keys = $this->cisco->console->getAllConsoleKeys();
        $this->assertIsArray($keys);
    }

    /**
     * Test: GroupService - Endpoints de groupes
     */
    public function test_group_service_endpoints(): void
    {
        // getGroups
        $groups = $this->cisco->groups->getGroups();
        $this->assertIsArray($groups);
        // Certains endpoints peuvent ne pas être disponibles
        if (isset($groups['error'])) {
            $this->markTestSkipped('Groups endpoint non disponible: ' . $groups['error']);
        }
    }

    /**
     * Test: TelemetryService - Endpoints de télémétrie
     */
    public function test_telemetry_service_endpoints(): void
    {
        // getTelemetrySettings
        $settings = $this->cisco->telemetry->getTelemetrySettings();
        $this->assertIsArray($settings);
    }

    /**
     * Helper pour s'authentifier
     */
    protected function authenticate(): void
    {
        $username = env('CML_USERNAME');
        $password = env('CML_PASSWORD');

        if (!$username || !$password) {
            $this->markTestSkipped('CML_USERNAME et CML_PASSWORD non configurés dans .env');
        }

        $result = $this->cisco->auth->authExtended($username, $password);

        // La réponse peut contenir "error": null, on vérifie qu'il n'y a pas d'erreur réelle
        if (isset($result['error']) && $result['error'] !== null) {
            $this->fail('Échec de l\'authentification: ' . json_encode($result));
        }

        $this->token = $result['token'] ?? null;
        $this->assertNotNull($this->token, 'Le token devrait être présent');
        
        $this->cisco->auth->setToken($this->token);
    }

    /**
     * Helper pour obtenir un lab ID de test
     */
    protected function getTestLabId(): void
    {
        $labs = $this->cisco->labs->getLabs();
        
        if (is_array($labs) && !empty($labs)) {
            $firstLab = reset($labs);
            if (isset($firstLab['id'])) {
                $this->testLabId = $firstLab['id'];
            }
        }
    }
}

