<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\UsageRecord;
use App\Models\Reservation;
use App\Models\User;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_creation_and_conflict()
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-uuid-1', 'name' => 'Lab One']);

        $this->actingAs($user)
            ->postJson("/api/labs/{$lab->id}/reserve", [
                'start_at' => now()->addHour()->toDateTimeString(),
                'end_at' => now()->addHours(2)->toDateTimeString(),
            ])
            ->assertStatus(201);

        $this->assertDatabaseCount('reservations', 1);

        // create conflicting reservation
        $existing = Reservation::first();

        $this->actingAs($user)
            ->postJson("/api/labs/{$lab->id}/reserve", [
                'start_at' => now()->addHour()->addMinutes(30)->toDateTimeString(),
                'end_at' => now()->addHours(3)->toDateTimeString(),
            ])
            ->assertStatus(422);
    }

    public function test_start_stop_creates_usage_record()
    {
        $user = User::factory()->create();
        $lab = Lab::create(['cml_id' => 'lab-uuid-2', 'name' => 'Lab Two']);

        // bind a fake CiscoApiService that returns success
        $this->app->singleton(CiscoApiService::class, function () {
            return new class extends CiscoApiService {
                public function __construct()
                {
                    // Pas d'initialisation parent requise pour le test.
                }

                public function startLab($tokenOrId, $idOrNull = null): array
                {
                    return ['ok' => true, 'id' => $idOrNull ?? $tokenOrId];
                }

                public function stopLab($tokenOrId, $idOrNull = null): array
                {
                    return ['ok' => true, 'id' => $idOrNull ?? $tokenOrId];
                }
            };
        });

        // start
        $this->actingAs($user)
            ->postJson("/api/labs/{$lab->id}/start")
            ->assertStatus(200)
            ->assertJsonFragment(['started' => true]);

        $this->assertDatabaseHas('usage_records', ['lab_id' => $lab->id]);

        $usage = UsageRecord::where('lab_id', $lab->id)->first();
        $this->assertNotNull($usage->started_at);
        $this->assertNull($usage->ended_at);

        // stop
        $this->actingAs($user)
            ->postJson("/api/labs/{$lab->id}/stop")
            ->assertStatus(200)
            ->assertJsonFragment(['stopped' => true]);

        $usage->refresh();
        $this->assertNotNull($usage->ended_at);
        $this->assertGreaterThan(0, $usage->duration_seconds);
    }
}


