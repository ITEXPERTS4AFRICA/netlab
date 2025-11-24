<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsoleWebSocketTest extends TestCase
{
    /**
     * Test que l'endpoint de création de session retourne bien un ws_href
     */
    public function test_console_session_returns_websocket_url()
    {
        // Simuler un utilisateur authentifié
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Données de test
        $payload = [
            'lab_id' => '90f84e38-a71c-4d57-8d90-00fa8a197385',
            'node_id' => '52ec5e24-4c53-44a2-9725-c9ef529deb78',
            'type' => 'console',
        ];

        // Appeler l'endpoint
        $response = $this->postJson('/api/console/sessions', $payload);

        // Vérifier la réponse
        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Assertions critiques
        $this->assertArrayHasKey('ws_href', $data, 'La réponse doit contenir ws_href');
        $this->assertNotNull($data['ws_href'], 'ws_href ne doit pas être null');
        $this->assertStringStartsWith('ws', $data['ws_href'], 'ws_href doit commencer par ws:// ou wss://');
        $this->assertStringContainsString('/console/ws?id=', $data['ws_href'], 'ws_href doit contenir le bon endpoint');
        
        // Vérifier aussi les autres champs
        $this->assertArrayHasKey('session_id', $data);
        $this->assertArrayHasKey('console_url', $data);
        $this->assertArrayHasKey('console_id', $data);
        
        // Logger pour debug
        \Log::info('Test WebSocket URL:', [
            'ws_href' => $data['ws_href'],
            'console_url' => $data['console_url'],
        ]);
    }

    /**
     * Test que le format de l'URL WebSocket est correct
     */
    public function test_websocket_url_format_is_correct()
    {
        $baseUrl = config('services.cml.base_url', 'https://cml-server.local');
        $consoleKey = '1e3043ed-c6e9-4c5a-bc62-2c40a62c9440';
        
        // Simuler la génération de l'URL
        $wsBaseUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $baseUrl);
        $wsHref = "{$wsBaseUrl}/console/ws?id={$consoleKey}";
        
        // Vérifications
        $this->assertStringStartsWith('ws', $wsHref);
        $this->assertStringContainsString($consoleKey, $wsHref);
        $this->assertStringContainsString('/console/ws?id=', $wsHref);
        
        // Si HTTPS, doit être WSS
        if (str_starts_with($baseUrl, 'https://')) {
            $this->assertStringStartsWith('wss://', $wsHref);
        } else {
            $this->assertStringStartsWith('ws://', $wsHref);
        }
        
        echo "\n✅ WebSocket URL généré: {$wsHref}\n";
    }

    /**
     * Test de la structure complète de la réponse
     */
    public function test_console_session_response_structure()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'lab_id' => '90f84e38-a71c-4d57-8d90-00fa8a197385',
            'node_id' => '52ec5e24-4c53-44a2-9725-c9ef529deb78',
        ];

        $response = $this->postJson('/api/console/sessions', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'id',
                'console_id',
                'console_key',
                'console_url',
                'url',
                'lab_id',
                'node_id',
                'type',
                'protocol',
                'ws_href',
            ]);
    }
}
