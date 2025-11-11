<?php

namespace Tests\Feature\Api;

use App\Models\Lab;
use App\Models\User;
use App\Services\Cisco\LabService;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public array $labsResponses = [
                    'list' => [
                        [
                            'id' => 'lab-1',
                            'name' => 'Topology 1',
                            'state' => 'RUNNING',
                        ],
                    ],
                    'detail' => [
                        'id' => 'lab-1',
                        'name' => 'Topology 1',
                        'nodes' => 4,
                    ],
                    'topology' => [
                        'lab' => 'lab-1',
                        'nodes' => [],
                        'links' => [],
                    ],
                    'state' => [
                        'lab_id' => 'lab-1',
                        'state' => 'RUNNING',
                    ],
                    'convergence' => [
                        'lab_id' => 'lab-1',
                        'converged' => true,
                    ],
                ];

                public function __construct()
                {
                    $this->labs = new class($this) extends LabService {
                        public function __construct(private readonly CiscoApiService $parent)
                        {
                            // Pas d'initialisation parent nécessaire pour le stub.
                        }

                        public function getLabs(): array
                        {
                            return $this->parent->labsResponses['list'];
                        }

                        public function getLab(string $id): array
                        {
                            return $this->parent->labsResponses['detail'];
                        }

                        public function getTopology(string $id): array
                        {
                            return $this->parent->labsResponses['topology'];
                        }

                        public function getLabState(string $id): array
                        {
                            return $this->parent->labsResponses['state'];
                        }

                        public function checkIfConverged(string $id): array
                        {
                            return $this->parent->labsResponses['convergence'];
                        }
                    };
                }
            };
        });
    }

    public function test_it_lists_labs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/labs')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Topology 1']);
    }

    public function test_it_returns_lab_topology(): void
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-1', 'name' => 'Lab One']);

        $this->actingAs($user)
            ->getJson("/api/labs/{$lab->id}/topology")
            ->assertOk()
            ->assertJsonFragment(['lab' => 'lab-1']);
    }

    public function test_it_returns_lab_state(): void
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-1', 'name' => 'Lab One']);

        $this->actingAs($user)
            ->getJson("/api/labs/{$lab->id}/state")
            ->assertOk()
            ->assertJsonFragment(['state' => 'RUNNING']);
    }

    public function test_it_checks_lab_convergence(): void
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-1', 'name' => 'Lab One']);

        $this->actingAs($user)
            ->getJson("/api/labs/{$lab->id}/convergence")
            ->assertOk()
            ->assertJsonFragment(['converged' => true]);
    }

    public function test_it_returns_lab_detail(): void
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-1', 'name' => 'Lab One']);

        $this->actingAs($user)
            ->getJson("/api/labs/{$lab->id}")
            ->assertOk()
            ->assertJsonFragment(['nodes' => 4]);
    }
}


