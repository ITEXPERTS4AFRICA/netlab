<?php

namespace Tests\Feature;

use App\Services\Cisco\AuthService;
use App\Services\Cisco\LabService;
use App\Services\Cisco\SystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests de connexion réelle à l'API Cisco CML
 * 
 * Ces tests vérifient que la connexion fonctionne avec un serveur CML réel.
 * Configurez CML_API_BASE_URL, CML_USERNAME et CML_PASSWORD dans votre .env
 */
class CmlConnectionTest extends TestCase
{
    protected ?string $token = null;
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authService = new AuthService();
        
        // Vérifier que la configuration CML est présente
        if (!config('services.cml.base_url')) {
            $this->markTestSkipped('CML_API_BASE_URL non configuré dans .env');
        }
    }

    /**
     * Test de connexion de base à l'API CML
     */
    public function test_can_connect_to_cml_api(): void
    {
        $username = env('CML_USERNAME');
        $password = env('CML_PASSWORD');
        
        if (!$username || !$password) {
            $this->markTestSkipped('CML_USERNAME et CML_PASSWORD non configurés dans .env');
        }

        $result = $this->authService->authExtended($username, $password);

        $this->assertIsArray($result);
        // La réponse peut contenir "error": null, on vérifie qu'il n'y a pas d'erreur réelle
        if (isset($result['error']) && $result['error'] !== null) {
            $this->fail('Erreur de connexion: ' . json_encode($result));
        }
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);

        $this->token = $result['token'];
        $this->authService->setToken($this->token);
    }

    /**
     * Test de vérification d'authentification
     */
    public function test_can_verify_authentication(): void
    {
        $this->authenticate();

        $result = $this->authService->authOk();

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
    }

    /**
     * Test de récupération des informations système
     */
    public function test_can_get_system_information(): void
    {
        $this->authenticate();

        $systemService = new SystemService();
        $systemService->setToken($this->token);

        $result = $systemService->getSystemInformation();

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
    }

    /**
     * Test de récupération de la liste des labs
     */
    public function test_can_get_labs_list(): void
    {
        $this->authenticate();

        $labService = new LabService();
        $labService->setToken($this->token);

        $result = $labService->getLabs();

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
    }

    /**
     * Test de déconnexion
     */
    public function test_can_logout(): void
    {
        $this->authenticate();

        $result = $this->authService->logout();

        // La déconnexion peut retourner différentes réponses selon l'API
        $this->assertTrue(
            is_array($result) || $result->successful(),
            'La déconnexion devrait réussir'
        );
    }

    /**
     * Helper pour s'authentifier avant chaque test nécessitant un token
     */
    protected function authenticate(): void
    {
        if ($this->token) {
            $this->authService->setToken($this->token);
            return;
        }

        $username = env('CML_USERNAME');
        $password = env('CML_PASSWORD');

        if (!$username || !$password) {
            $this->markTestSkipped('CML_USERNAME et CML_PASSWORD non configurés');
        }

        $result = $this->authService->authExtended($username, $password);

        // La réponse peut contenir "error": null, on vérifie qu'il n'y a pas d'erreur réelle
        if (isset($result['error']) && $result['error'] !== null) {
            $this->fail('Échec de l\'authentification: ' . json_encode($result));
        }

        $this->token = $result['token'] ?? null;
        $this->assertNotNull($this->token, 'Le token devrait être présent après authentification');
    }
}

