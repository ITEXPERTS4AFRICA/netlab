<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsolePollingApiTest extends TestCase
{
    /**
     * Test que les routes de polling sont bien enregistrées
     */
    public function test_polling_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn($route) => $route->uri());

        // Vérifier que les routes existent
        $this->assertTrue(
            $routes->contains('api/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/poll'),
            'Route de polling non trouvée'
        );
        
        $this->assertTrue(
            $routes->contains('api/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/cache'),
            'Route de cache non trouvée'
        );
        
        $this->assertTrue(
            $routes->contains('api/console/ping'),
            'Route ping non trouvée'
        );
    }

    /**
     * Test que les méthodes HTTP sont correctes
     */
    public function test_polling_routes_http_methods(): void
    {
        $routes = collect(\Route::getRoutes());

        // Route de polling doit être GET
        $pollingRoute = $routes->first(fn($route) => 
            str_contains($route->uri(), 'consoles/{consoleId}/poll')
        );
        $this->assertNotNull($pollingRoute);
        $this->assertContains('GET', $pollingRoute->methods());

        // Route de cache doit être DELETE
        $cacheRoute = $routes->first(fn($route) => 
            str_contains($route->uri(), 'consoles/{consoleId}/cache')
        );
        $this->assertNotNull($cacheRoute);
        $this->assertContains('DELETE', $cacheRoute->methods());

        // Route ping doit être GET
        $pingRoute = $routes->first(fn($route) => 
            $route->uri() === 'api/console/ping'
        );
        $this->assertNotNull($pingRoute);
        $this->assertContains('GET', $pingRoute->methods());
    }
}

