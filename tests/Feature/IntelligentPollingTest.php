<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Console\IntelligentPollingService;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class IntelligentPollingTest extends TestCase
{
    protected CiscoApiService $cisco;
    protected IntelligentPollingService $polling;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cisco = $this->app->make(CiscoApiService::class);
        $this->polling = new IntelligentPollingService($this->cisco);
    }

    /**
     * Test que le service de polling peut être instancié
     */
    public function test_polling_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(IntelligentPollingService::class, $this->polling);
    }

    /**
     * Test de normalisation des logs
     */
    public function test_normalize_logs_with_array(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('normalizeLogs');
        $method->setAccessible(true);

        // Test avec un tableau de logs
        $response = ['log' => ['line1', 'line2', 'line3']];
        $result = $method->invoke($this->polling, $response);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('line1', $result[0]);
    }

    /**
     * Test de normalisation des logs avec string
     */
    public function test_normalize_logs_with_string(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('normalizeLogs');
        $method->setAccessible(true);

        // Test avec une string
        $response = ['log' => "line1\nline2\nline3"];
        $result = $method->invoke($this->polling, $response);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test de détection des nouvelles lignes
     */
    public function test_detect_new_logs(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('detectNewLogs');
        $method->setAccessible(true);

        $cachedLogs = ['line1', 'line2'];
        $newLogs = ['line1', 'line2', 'line3', 'line4'];
        
        $result = $method->invoke($this->polling, $cachedLogs, $newLogs);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('line3', $result[0]);
        $this->assertEquals('line4', $result[1]);
    }

    /**
     * Test de parsing des prompts IOS
     */
    public function test_parse_ios_prompts(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('parseIOSLogs');
        $method->setAccessible(true);

        $logs = [
            'Router>',
            'Router>enable',
            'Router#',
            'Router#configure terminal',
            'Router(config)#',
            'Router(config)#hostname Switch1',
            'Switch1(config)#',
        ];
        
        $result = $method->invoke($this->polling, $logs);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('prompts', $result);
        $this->assertArrayHasKey('commands', $result);
        $this->assertArrayHasKey('current_mode', $result);
        $this->assertArrayHasKey('hostname', $result);
        
        // Vérifier que les prompts sont détectés
        $this->assertGreaterThan(0, count($result['prompts']));
        
        // Vérifier que le hostname est détecté
        $this->assertEquals('Switch1', $result['hostname']);
    }

    /**
     * Test de parsing des commandes IOS
     */
    public function test_parse_ios_commands(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('parseIOSLogs');
        $method->setAccessible(true);

        $logs = [
            'Router>show version',
            'Cisco IOS Software, Version 15.2',
            'Router>',
        ];
        
        $result = $method->invoke($this->polling, $logs);
        
        $this->assertArrayHasKey('commands', $result);
        $this->assertGreaterThan(0, count($result['commands']));
        
        // Vérifier que la commande est détectée
        $this->assertEquals('show version', $result['commands'][0]['command']);
    }

    /**
     * Test de détection du mode IOS
     */
    public function test_detect_ios_mode(): void
    {
        $reflection = new \ReflectionClass($this->polling);
        $method = $reflection->getMethod('parseIOSLogs');
        $method->setAccessible(true);

        // Mode user
        $logs = ['Router>'];
        $result = $method->invoke($this->polling, $logs);
        $this->assertEquals('user', $result['current_mode']);

        // Mode privileged
        $logs = ['Router#'];
        $result = $method->invoke($this->polling, $logs);
        $this->assertEquals('privileged', $result['current_mode']);

        // Mode config
        $logs = ['Router(config)#'];
        $result = $method->invoke($this->polling, $logs);
        $this->assertEquals('config', $result['current_mode']);
    }

    /**
     * Test du cache
     */
    public function test_cache_clearing(): void
    {
        $labId = 'test-lab';
        $nodeId = 'test-node';
        $consoleId = 'test-console';
        
        $cacheKey = "console_logs:{$labId}:{$nodeId}:{$consoleId}";
        
        // Mettre quelque chose en cache
        Cache::put($cacheKey, ['test'], 60);
        $this->assertTrue(Cache::has($cacheKey));
        
        // Vider le cache
        $this->polling->clearCache($labId, $nodeId, $consoleId);
        
        // Vérifier que le cache est vidé
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test de l'intervalle de polling
     */
    public function test_polling_interval_configuration(): void
    {
        $this->polling->setPollInterval(5000);
        $this->assertEquals(5000, $this->polling->getRecommendedPollInterval());
    }
}
