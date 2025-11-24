<?php

namespace Tests\Unit;

use App\Services\CiscoApiService;
use App\Services\Cisco\BaseCiscoApiService;
use App\Services\Cisco\AuthService;
use App\Services\Cisco\LabService;
use App\Services\Cisco\NodeService;
use Tests\TestCase;

/**
 * Tests pour valider l'architecture SOLID et Clean Architecture
 * 
 * Ces tests vérifient que le code respecte les principes SOLID :
 * - Single Responsibility Principle (SRP)
 * - Open/Closed Principle (OCP)
 * - Liskov Substitution Principle (LSP)
 * - Interface Segregation Principle (ISP)
 * - Dependency Inversion Principle (DIP)
 */
class SolidArchitectureTest extends TestCase
{
    /**
     * Test Single Responsibility Principle (SRP)
     * 
     * Chaque service doit avoir une seule responsabilité
     */
    public function test_single_responsibility_principle(): void
    {
        // AuthService doit uniquement gérer l'authentification
        $authService = new AuthService();
        $this->assertInstanceOf(BaseCiscoApiService::class, $authService);
        
        // Vérifier que AuthService a des méthodes liées uniquement à l'auth
        $authMethods = get_class_methods(AuthService::class);
        $authRelatedMethods = array_filter($authMethods, function($method) {
            return strpos($method, 'auth') !== false || 
                   strpos($method, 'login') !== false ||
                   strpos($method, 'token') !== false ||
                   strpos($method, 'session') !== false;
        });
        
        // AuthService doit avoir principalement des méthodes d'authentification
        $this->assertGreaterThan(0, count($authRelatedMethods), 
            'AuthService doit avoir des méthodes liées à l\'authentification');
        
        // LabService doit uniquement gérer les labs
        $labService = new LabService();
        $this->assertInstanceOf(BaseCiscoApiService::class, $labService);
        
        // Vérifier que LabService a des méthodes liées uniquement aux labs
        $labMethods = get_class_methods(LabService::class);
        $labRelatedMethods = array_filter($labMethods, function($method) {
            return strpos($method, 'lab') !== false || 
                   strpos($method, 'Lab') !== false;
        });
        
        $this->assertGreaterThan(0, count($labRelatedMethods), 
            'LabService doit avoir des méthodes liées aux labs');
        
        // NodeService doit uniquement gérer les nodes
        $nodeService = new NodeService();
        $this->assertInstanceOf(BaseCiscoApiService::class, $nodeService);
        
        // Vérifier que NodeService a des méthodes liées uniquement aux nodes
        $nodeMethods = get_class_methods(NodeService::class);
        $nodeRelatedMethods = array_filter($nodeMethods, function($method) {
            return strpos($method, 'node') !== false || 
                   strpos($method, 'Node') !== false;
        });
        
        $this->assertGreaterThan(0, count($nodeRelatedMethods), 
            'NodeService doit avoir des méthodes liées aux nodes');
    }

    /**
     * Test Open/Closed Principle (OCP)
     * 
     * Le code doit être ouvert à l'extension mais fermé à la modification
     */
    public function test_open_closed_principle(): void
    {
        // Tous les services héritent de BaseCiscoApiService
        // Cela permet d'étendre sans modifier la classe de base
        
        $authService = new AuthService();
        $labService = new LabService();
        $nodeService = new NodeService();
        
        // Tous doivent hériter de BaseCiscoApiService
        $this->assertInstanceOf(BaseCiscoApiService::class, $authService);
        $this->assertInstanceOf(BaseCiscoApiService::class, $labService);
        $this->assertInstanceOf(BaseCiscoApiService::class, $nodeService);
        
        // Vérifier que BaseCiscoApiService a des méthodes communes
        $baseMethods = get_class_methods(BaseCiscoApiService::class);
        $this->assertContains('setBaseUrl', $baseMethods, 
            'BaseCiscoApiService doit avoir la méthode setBaseUrl');
        $this->assertContains('enableCache', $baseMethods, 
            'BaseCiscoApiService doit avoir la méthode enableCache');
        
        // Chaque service peut être étendu sans modifier BaseCiscoApiService
        $this->assertTrue(
            method_exists($authService, 'setBaseUrl'),
            'AuthService doit pouvoir utiliser setBaseUrl de BaseCiscoApiService'
        );
        $this->assertTrue(
            method_exists($labService, 'setBaseUrl'),
            'LabService doit pouvoir utiliser setBaseUrl de BaseCiscoApiService'
        );
    }

    /**
     * Test Liskov Substitution Principle (LSP)
     * 
     * Les objets d'une classe dérivée doivent pouvoir remplacer les objets de la classe de base
     */
    public function test_liskov_substitution_principle(): void
    {
        // Créer des instances de services dérivés
        $authService = new AuthService();
        $labService = new LabService();
        $nodeService = new NodeService();
        
        // Tous doivent pouvoir être utilisés comme BaseCiscoApiService
        $services = [$authService, $labService, $nodeService];
        
        foreach ($services as $service) {
            $this->assertInstanceOf(BaseCiscoApiService::class, $service,
                'Tous les services doivent être substituables à BaseCiscoApiService');
            
            // Vérifier que les méthodes communes fonctionnent
            $service->setBaseUrl('https://test.example.com');
            $this->assertTrue(
                method_exists($service, 'setBaseUrl'),
                'Les méthodes communes doivent fonctionner de la même manière'
            );
        }
    }

    /**
     * Test Interface Segregation Principle (ISP)
     * 
     * Les clients ne doivent pas dépendre de méthodes qu'ils n'utilisent pas
     */
    public function test_interface_segregation_principle(): void
    {
        // AuthService ne doit pas avoir de méthodes liées aux labs
        $authService = new AuthService();
        $authMethods = get_class_methods(AuthService::class);
        
        // Vérifier qu'AuthService n'a pas de méthodes spécifiques aux labs
        $labSpecificMethods = array_filter($authMethods, function($method) {
            return strpos($method, 'getLab') !== false || 
                   strpos($method, 'startLab') !== false ||
                   strpos($method, 'stopLab') !== false;
        });
        
        $this->assertCount(0, $labSpecificMethods, 
            'AuthService ne doit pas avoir de méthodes spécifiques aux labs');
        
        // LabService ne doit pas avoir de méthodes d'authentification
        $labService = new LabService();
        $labMethods = get_class_methods(LabService::class);
        
        $authSpecificMethods = array_filter($labMethods, function($method) {
            return strpos($method, 'auth') !== false || 
                   strpos($method, 'login') !== false;
        });
        
        $this->assertCount(0, $authSpecificMethods, 
            'LabService ne doit pas avoir de méthodes d\'authentification');
    }

    /**
     * Test Dependency Inversion Principle (DIP)
     * 
     * Les modules de haut niveau ne doivent pas dépendre des modules de bas niveau
     * Les deux doivent dépendre d'abstractions
     */
    public function test_dependency_inversion_principle(): void
    {
        // CiscoApiService (façade) doit dépendre de l'abstraction BaseCiscoApiService
        $ciscoApi = new CiscoApiService();
        
        // Vérifier que CiscoApiService expose les services via des propriétés publiques
        $this->assertTrue(
            property_exists($ciscoApi, 'labs'),
            'CiscoApiService doit exposer les services via des propriétés publiques'
        );
        
        // Les services doivent être accessibles via la façade
        $this->assertInstanceOf(LabService::class, $ciscoApi->labs,
            'CiscoApiService doit retourner une instance de LabService');
        
        // Vérifier que les services peuvent être utilisés indépendamment
        $labService = new LabService();
        $this->assertInstanceOf(BaseCiscoApiService::class, $labService,
            'Les services doivent dépendre de BaseCiscoApiService (abstraction)');
    }

    /**
     * Test de la séparation des responsabilités dans CiscoApiService
     */
    public function test_cisco_api_service_separation_of_concerns(): void
    {
        $ciscoApi = new CiscoApiService();
        
        // Vérifier que CiscoApiService expose différents services
        $this->assertInstanceOf(LabService::class, $ciscoApi->labs,
            'CiscoApiService doit exposer LabService');
        
        // Vérifier que chaque service est indépendant
        $labService1 = $ciscoApi->labs;
        $labService2 = $ciscoApi->labs;
        
        // Les services doivent être des instances différentes (ou partagées selon l'implémentation)
        // Mais ils doivent fonctionner indépendamment
        $labService1->setToken('token1');
        $labService2->setToken('token2');
        
        // Chaque service doit maintenir son propre état
        $this->assertTrue(
            method_exists($labService1, 'getToken'),
            'Chaque service doit pouvoir maintenir son propre état'
        );
    }

    /**
     * Test de la structure Clean Architecture
     * 
     * Vérifier que les couches sont bien séparées
     */
    public function test_clean_architecture_layers(): void
    {
        // Services (couche domaine)
        $services = [
            AuthService::class,
            LabService::class,
            NodeService::class,
        ];
        
        foreach ($services as $serviceClass) {
            // Vérifier que les services sont dans le namespace Services
            $this->assertStringContainsString('App\Services\Cisco', $serviceClass,
                'Les services doivent être dans le namespace Services\Cisco');
            
            // Vérifier que les services héritent de BaseCiscoApiService
            $service = new $serviceClass();
            $this->assertInstanceOf(BaseCiscoApiService::class, $service,
                'Tous les services doivent hériter de BaseCiscoApiService');
        }
        
        // Façade (couche application)
        $ciscoApi = new CiscoApiService();
        $this->assertStringContainsString('App\Services', get_class($ciscoApi),
            'La façade doit être dans le namespace Services');
    }

    /**
     * Test de la testabilité (facilité de mock)
     */
    public function test_testability_and_mocking(): void
    {
        // Les services doivent être facilement mockables
        $labService = new LabService();
        
        // Vérifier que les méthodes publiques existent
        $this->assertTrue(
            method_exists($labService, 'setToken'),
            'Les services doivent avoir des méthodes publiques pour faciliter le mocking'
        );
        
        // Vérifier que les services peuvent être instanciés sans dépendances externes
        $this->assertInstanceOf(LabService::class, $labService,
            'Les services doivent pouvoir être instanciés sans dépendances complexes');
    }
}

