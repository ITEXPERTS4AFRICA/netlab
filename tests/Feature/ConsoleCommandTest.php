<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class ConsoleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected CiscoApiService $ciscoApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ciscoApi = new CiscoApiService();
    }

    /**
     * Test: Vérifier que les consoles sont accessibles pour un node
     */
    public function test_can_get_node_consoles(): void
    {
        // Simuler un token CML (dans un vrai test, vous devriez vous authentifier)
        $token = env('CML_TOKEN', 'test-token');
        Session::put('cml_token', $token);

        // Récupérer un lab et un node existant (vous devrez adapter ces IDs)
        $labId = env('TEST_LAB_ID', 'test-lab-id');
        $nodeId = env('TEST_NODE_ID', 'test-node-id');

        // Si les IDs de test ne sont pas configurés, skip le test
        if ($labId === 'test-lab-id' || $nodeId === 'test-node-id') {
            $this->markTestSkipped('IDs de test non configurés. Définissez TEST_LAB_ID et TEST_NODE_ID dans .env');
        }

        $this->ciscoApi->setToken($token);

        $consoles = $this->ciscoApi->console->getNodeConsoles($labId, $nodeId);

        $this->assertIsArray($consoles);
        $this->assertArrayHasKey('consoles', $consoles);
    }

    /**
     * Test: Vérifier que la clé console peut être obtenue
     */
    public function test_can_get_console_key(): void
    {
        $token = env('CML_TOKEN', 'test-token');
        Session::put('cml_token', $token);

        $labId = env('TEST_LAB_ID', 'test-lab-id');
        $nodeId = env('TEST_NODE_ID', 'test-node-id');

        if ($labId === 'test-lab-id' || $nodeId === 'test-node-id') {
            $this->markTestSkipped('IDs de test non configurés');
        }

        $this->ciscoApi->setToken($token);

        $consoleKey = $this->ciscoApi->console->getNodeConsoleKey($labId, $nodeId);

        $this->assertIsArray($consoleKey);
        // La clé console devrait contenir une URL ou un identifiant
        $this->assertTrue(
            isset($consoleKey['console_key']) || 
            isset($consoleKey['url']) || 
            isset($consoleKey['key']) ||
            !isset($consoleKey['error'])
        );
    }

    /**
     * Test: Vérifier que les logs de console peuvent être récupérés
     */
    public function test_can_get_console_logs(): void
    {
        $token = env('CML_TOKEN', 'test-token');
        Session::put('cml_token', $token);

        $labId = env('TEST_LAB_ID', 'test-lab-id');
        $nodeId = env('TEST_NODE_ID', 'test-node-id');
        $consoleId = env('TEST_CONSOLE_ID', 'test-console-id');

        if ($labId === 'test-lab-id' || $nodeId === 'test-node-id' || $consoleId === 'test-console-id') {
            $this->markTestSkipped('IDs de test non configurés');
        }

        $this->ciscoApi->setToken($token);

        $logs = $this->ciscoApi->console->getConsoleLog($labId, $nodeId, $consoleId);

        $this->assertIsArray($logs);
        // Les logs peuvent être vides, mais la structure devrait être correcte
        $this->assertTrue(
            isset($logs['log']) || 
            isset($logs['output']) || 
            isset($logs['data']) ||
            !isset($logs['error'])
        );
    }

    /**
     * Test: Vérifier que les types de console disponibles sont retournés
     */
    public function test_can_get_available_console_types(): void
    {
        $token = env('CML_TOKEN', 'test-token');
        Session::put('cml_token', $token);

        $labId = env('TEST_LAB_ID', 'test-lab-id');
        $nodeId = env('TEST_NODE_ID', 'test-node-id');

        if ($labId === 'test-lab-id' || $nodeId === 'test-node-id') {
            $this->markTestSkipped('IDs de test non configurés');
        }

        $this->ciscoApi->setToken($token);

        $types = $this->ciscoApi->console->getAvailableConsoleTypes($labId, $nodeId);

        $this->assertIsArray($types);
        $this->assertArrayHasKey('console', $types);
        // La console devrait toujours être disponible
        $this->assertTrue($types['console'] === true);
    }

    /**
     * Test: Vérifier que l'API console répond correctement
     */
    public function test_console_api_is_accessible(): void
    {
        $token = env('CML_TOKEN', 'test-token');
        
        if ($token === 'test-token') {
            $this->markTestSkipped('Token CML non configuré. Définissez CML_TOKEN dans .env');
        }

        Session::put('cml_token', $token);
        $this->ciscoApi->setToken($token);

        // Tester l'accès à l'endpoint de base des consoles
        $response = $this->getJson('/api/console/ping');

        // L'endpoint ping devrait toujours répondre
        $response->assertStatus(200);
    }

    /**
     * Test: Vérifier la structure de réponse des consoles
     */
    public function test_console_response_structure(): void
    {
        $token = env('CML_TOKEN', 'test-token');
        Session::put('cml_token', $token);

        $labId = env('TEST_LAB_ID', 'test-lab-id');
        $nodeId = env('TEST_NODE_ID', 'test-node-id');

        if ($labId === 'test-lab-id' || $nodeId === 'test-node-id') {
            $this->markTestSkipped('IDs de test non configurés');
        }

        $this->ciscoApi->setToken($token);

        $consoles = $this->ciscoApi->console->getNodeConsoles($labId, $nodeId);

        // Vérifier la structure de base
        $this->assertIsArray($consoles);
        
        if (isset($consoles['consoles']) && is_array($consoles['consoles'])) {
            foreach ($consoles['consoles'] as $console) {
                $this->assertIsArray($console);
                // Chaque console devrait avoir un ID ou un type
                $this->assertTrue(
                    isset($console['id']) || 
                    isset($console['console_id']) ||
                    isset($console['type'])
                );
            }
        }
    }
}


