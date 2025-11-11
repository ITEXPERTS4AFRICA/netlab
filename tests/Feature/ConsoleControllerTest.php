<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Cisco\ConsoleService;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    // Ne pas appeler le constructeur parent : on injecte un faux ConsoleService.
                    $this->console = new class extends ConsoleService {
                        public function __construct()
                        {
                            // Pas d’appel parent.
                        }

                        public function getNodeConsoles(string $labId, string $nodeId): array
                        {
                            return [
                                ['id' => 'console-1', 'console_type' => 'serial'],
                                ['id' => 'console-2', 'console_type' => 'console'],
                            ];
                        }

                        public function getAvailableConsoleTypes(string $labId, string $nodeId): array
                        {
                            return [
                                'console' => true,
                                'serial' => true,
                                'vnc' => false,
                            ];
                        }

                        public function createConsoleSession(string $labId, string $nodeId, array $options = []): array
                        {
                            return [
                                'session_id' => 'session-123',
                                'lab_id' => $labId,
                                'node_id' => $nodeId,
                                'type' => $options['type'] ?? 'console',
                                'ws_href' => 'wss://example.test/ws',
                            ];
                        }

                        public function getConsoleSessions(): array
                        {
                            return [
                                'sessions' => [
                                    [
                                        'session_id' => 'session-123',
                                        'lab_id' => 'lab-1',
                                        'node_id' => 'node-1',
                                        'type' => 'console',
                                    ],
                                ],
                            ];
                        }

                        public function closeConsoleSession(string $sessionId): array
                        {
                            return [
                                'session_id' => $sessionId,
                                'closed' => true,
                            ];
                        }

                        public function getConsoleLog(string $labId, string $nodeId, string $consoleId): array
                        {
                            return [
                                'log' => [
                                    'line 1',
                                    'line 2',
                                ],
                            ];
                        }
                    };
                }
            };
        });
    }

    public function test_it_lists_consoles_for_node()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/labs/lab-1/nodes/node-1/consoles')
            ->assertOk()
            ->assertJsonFragment(['id' => 'console-1'])
            ->assertJsonFragment(['available_types' => [
                'console' => true,
                'serial' => true,
                'vnc' => false,
            ]]);
    }

    public function test_it_creates_console_session()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/console/sessions', [
                'lab_id' => 'lab-1',
                'node_id' => 'node-1',
                'type' => 'console',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'session_id' => 'session-123',
                'lab_id' => 'lab-1',
                'node_id' => 'node-1',
                'type' => 'console',
            ]);
    }

    public function test_it_returns_active_sessions()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/console/sessions')
            ->assertOk()
            ->assertJsonFragment([
                'session_id' => 'session-123',
            ]);
    }

    public function test_it_closes_console_session()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/console/sessions/session-123')
            ->assertOk()
            ->assertJsonFragment([
                'session_id' => 'session-123',
                'closed' => true,
            ]);
    }

    public function test_it_fetches_console_log()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/labs/lab-1/nodes/node-1/consoles/console-1/log')
            ->assertOk()
            ->assertJsonFragment([
                'log' => [
                    'line 1',
                    'line 2',
                ],
            ]);
    }
}


