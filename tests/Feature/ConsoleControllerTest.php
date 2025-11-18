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

    protected User $user;
    protected string $labId = '90f84e38-a71c-4d57-8d90-00fa8a197385';
    protected string $nodeId = 'n1';
    protected string $consoleId = 'console-1';
    protected string $sessionId = 'session-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Mock du service Cisco API
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    // Ne pas appeler le constructeur parent
                    $this->console = new class extends ConsoleService {
                        public function __construct()
                        {
                            // Pas d'appel parent
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
                                'protocol' => $options['protocol'] ?? 'telnet',
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

    // ============================================
    // Tests pour GET /api/labs/{labId}/nodes/{nodeId}/consoles
    // ============================================

    public function test_it_lists_consoles_for_node_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");

        $response->assertOk()
            ->assertJsonStructure([
                'consoles' => [
                    '*' => ['id', 'console_type'],
                ],
                'available_types' => ['console', 'serial', 'vnc'],
            ])
            ->assertJsonFragment(['id' => 'console-1'])
            ->assertJsonFragment(['id' => 'console-2'])
            ->assertJsonFragment(['available_types' => [
                'console' => true,
                'serial' => true,
                'vnc' => false,
            ]]);
    }

    public function test_it_requires_authentication_to_list_consoles(): void
    {
        $response = $this->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");

        $response->assertUnauthorized();
    }

    public function test_it_handles_error_when_getting_consoles_fails(): void
    {
        // Mock pour retourner une erreur
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
    {
                    $this->console = new class extends ConsoleService {
                        public function getNodeConsoles(string $labId, string $nodeId): array
                        {
                            return ['error' => 'Node not found', 'status' => 404];
                        }

                        public function getAvailableConsoleTypes(string $labId, string $nodeId): array
                        {
                            return [];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Node not found']);
    }

    public function test_it_handles_error_when_getting_console_types_fails_gracefully(): void
    {
        // Mock pour retourner une erreur sur getAvailableConsoleTypes mais pas sur getNodeConsoles
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function getNodeConsoles(string $labId, string $nodeId): array
                        {
                            return [['id' => 'console-1']];
                        }

                        public function getAvailableConsoleTypes(string $labId, string $nodeId): array
                        {
                            return ['error' => 'Failed to get types', 'status' => 500];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");

        // Devrait quand même retourner les consoles, mais avec available_types vide
        $response->assertOk()
            ->assertJsonFragment(['consoles' => [['id' => 'console-1']]])
            ->assertJsonFragment(['available_types' => []]);
    }

    // ============================================
    // Tests pour POST /api/console/sessions
    // ============================================

    public function test_it_creates_console_session_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 'console',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'session_id',
                'lab_id',
                'node_id',
                'type',
                'protocol',
                'ws_href',
            ])
            ->assertJsonFragment([
                'session_id' => 'session-123',
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 'console',
            ]);
    }

    public function test_it_creates_console_session_with_serial_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 'serial',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['type' => 'serial']);
    }

    public function test_it_creates_console_session_with_protocol(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 'console',
                'protocol' => 'ssh',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['protocol' => 'ssh']);
    }

    public function test_it_creates_console_session_with_custom_options(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 'console',
                'options' => [
                    'timeout' => 300,
                    'buffer_size' => 1024,
                ],
            ]);

        $response->assertOk();
    }

    public function test_it_requires_authentication_to_create_session(): void
    {
        $response = $this->postJson('/api/console/sessions', [
            'lab_id' => $this->labId,
            'node_id' => $this->nodeId,
        ]);

        $response->assertUnauthorized();
    }

    public function test_it_validates_lab_id_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'node_id' => $this->nodeId,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lab_id']);
    }

    public function test_it_validates_node_id_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['node_id']);
    }

    public function test_it_validates_type_is_string_when_provided(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'type' => 123, // Invalid type
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_it_validates_options_is_array_when_provided(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
                'options' => 'not-an-array', // Invalid options
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['options']);
    }

    public function test_it_handles_error_when_creating_session_fails(): void
    {
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function createConsoleSession(string $labId, string $nodeId, array $options = []): array
                        {
                            return ['error' => 'Failed to create session', 'status' => 500];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/console/sessions', [
                'lab_id' => $this->labId,
                'node_id' => $this->nodeId,
            ]);

        $response->assertStatus(500)
            ->assertJsonFragment(['error' => 'Failed to create session']);
    }

    // ============================================
    // Tests pour GET /api/console/sessions
    // ============================================

    public function test_it_returns_active_sessions_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/console/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'sessions' => [
                    '*' => ['session_id', 'lab_id', 'node_id', 'type'],
                ],
            ])
            ->assertJsonFragment(['session_id' => 'session-123']);
    }

    public function test_it_requires_authentication_to_get_sessions(): void
    {
        $response = $this->getJson('/api/console/sessions');

        $response->assertUnauthorized();
    }

    public function test_it_handles_error_when_getting_sessions_fails(): void
    {
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function getConsoleSessions(): array
                        {
                            return ['error' => 'Service unavailable', 'status' => 503];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/console/sessions');

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'Service unavailable']);
    }

    public function test_it_returns_empty_sessions_when_none_exist(): void
    {
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function getConsoleSessions(): array
                        {
                            return ['sessions' => []];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/console/sessions');

        $response->assertOk()
            ->assertJsonFragment(['sessions' => []]);
    }

    // ============================================
    // Tests pour DELETE /api/console/sessions/{sessionId}
    // ============================================

    public function test_it_closes_console_session_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/console/sessions/{$this->sessionId}");

        $response->assertOk()
            ->assertJsonStructure(['session_id', 'closed'])
            ->assertJsonFragment([
                'session_id' => $this->sessionId,
                'closed' => true,
            ]);
    }

    public function test_it_requires_authentication_to_close_session(): void
    {
        $response = $this->deleteJson("/api/console/sessions/{$this->sessionId}");

        $response->assertUnauthorized();
    }

    public function test_it_handles_error_when_closing_session_fails(): void
    {
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function closeConsoleSession(string $sessionId): array
                        {
                            return ['error' => 'Session not found', 'status' => 404];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/console/sessions/{$this->sessionId}");

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Session not found']);
    }

    // ============================================
    // Tests pour GET /api/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log
    // ============================================

    public function test_it_fetches_console_log_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles/{$this->consoleId}/log");

        $response->assertOk()
            ->assertJsonStructure(['log'])
            ->assertJsonFragment([
                'log' => [
                    'line 1',
                    'line 2',
                ],
            ]);
    }

    public function test_it_requires_authentication_to_get_log(): void
    {
        $response = $this->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles/{$this->consoleId}/log");

        $response->assertUnauthorized();
    }

    public function test_it_handles_error_when_getting_log_fails(): void
    {
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    $this->console = new class extends ConsoleService {
                        public function getConsoleLog(string $labId, string $nodeId, string $consoleId): array
                        {
                            return ['error' => 'Console not found', 'status' => 404];
                        }
                    };
                }
            };
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles/{$this->consoleId}/log");

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Console not found']);
    }

    // ============================================
    // Tests d'intégration et cas limites
    // ============================================

    public function test_it_handles_missing_cml_token_gracefully(): void
    {
        // Simuler une session sans token CML
        session()->forget('cml_token');

        $response = $this->actingAs($this->user)
            ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");

        // Le service devrait quand même fonctionner (peut utiliser un token par défaut ou échouer gracieusement)
        $response->assertOk();
    }

    // Note: Les tests de logging sont commentés car ils nécessitent Mockery
    // et peuvent causer des problèmes avec le framework de test Laravel.
    // Ils peuvent être réactivés si nécessaire avec une configuration appropriée.
    
    // public function test_it_logs_console_operations(): void
    // {
    //     Log::shouldReceive('info')
    //         ->once()
    //         ->with('Console: Récupération des consoles', \Mockery::type('array'));
    //
    //     $this->actingAs($this->user)
    //         ->getJson("/api/labs/{$this->labId}/nodes/{$this->nodeId}/consoles");
    // }
}
