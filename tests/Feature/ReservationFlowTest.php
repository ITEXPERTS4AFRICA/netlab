<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use App\Services\CinetPayService;
use App\Services\CiscoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Simuler le CiscoApiService pour éviter les appels réels
        $this->mock(CiscoApiService::class, function ($mock) {
            $mock->shouldReceive('setToken')->andReturnNull(); // Ajout important pour la sécurité
            $mock->shouldReceive('getLabState')->andReturn(['state' => 'STOPPED']);
            $mock->shouldReceive('getLabsAnnotation')->andReturn([]);
            $mock->shouldReceive('startLab')->andReturn(['status' => 'OK']);
        });
    }

    public function test_it_creates_reservation_but_handles_payment_timeout_gracefully()
    {
        $user = User::factory()->create();
        $lab = Lab::create([
            'cml_id' => 'lab-uuid-1', 
            'lab_title' => 'Test Lab',
            'price_cents' => 10000, // Lab payant
            'state' => 'STOPPED',
            'node_count' => 1,
            'created' => now(),
            'modified' => now(),
        ]);

        // Mocker CinetPayService pour simuler un timeout
        $cinetPayMock = Mockery::mock(CinetPayService::class);
        $cinetPayMock->shouldReceive('initiatePayment')
            ->once()
            ->andThrow(new \Exception('Connection timed out after 10000ms'));
        
        $this->app->instance(CinetPayService::class, $cinetPayMock);

        $startAt = now()->addHour()->format('Y-m-d H:i:00');
        $endAt = now()->addHours(5)->format('Y-m-d H:i:00');

        $response = $this->actingAs($user)
            ->postJson('/api/labs/reserve', [
                'lab_id' => $lab->cml_id,
                'start_at' => $startAt,
                'end_at' => $endAt,
            ]);

        // Debug en cas d'échec
        if ($response->status() !== 201) {
            dump($response->json());
        }

        // Vérifications
        $response->assertStatus(201); // Doit être 201 Created malgré l'erreur de paiement
        
        $response->assertJsonStructure([
            'reservation',
            'payment_error',
            'error',
            'can_retry_payment'
        ]);

        $response->assertJson([
            'payment_error' => true,
            'is_timeout' => true,
        ]);

        // Vérifier que la réservation est bien en base
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'status' => 'pending',
        ]);
    }

    public function test_it_blocks_lab_access_if_payment_is_required_but_not_completed()
    {
        $user = User::factory()->create();
        $lab = Lab::create([
            'cml_id' => 'lab-uuid-2', 
            'lab_title' => 'Paid Lab',
            'price_cents' => 50000,
            'state' => 'STOPPED',
            'node_count' => 1,
            'created' => now(),
            'modified' => now(),
        ]);

        // Créer une réservation "pending" sans paiement
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'start_at' => now()->subMinute(), // Active maintenant
            'end_at' => now()->addHour(),
            'status' => 'pending',
            'estimated_cents' => 50000
        ]);

        // 1. Tenter d'accéder au workspace (GET)
        $response = $this->actingAs($user)->get("/labs/{$lab->id}/workspace");
        
        // Doit rediriger vers la liste des réservations avec un message
        $response->assertRedirect(route('labs.my-reserved'));
        $response->assertSessionHas('warning');

        // 2. Tenter de démarrer le lab via l'API (POST)
        $responseApi = $this->actingAs($user)->postJson("/api/labs/{$lab->id}/start");
        
        // Doit être interdit (403)
        $responseApi->assertStatus(403);
        $responseApi->assertJson(['error' => 'Le paiement pour cette réservation n\'a pas été validé. Veuillez procéder au paiement.']);
    }

    public function test_it_allows_lab_access_if_payment_is_completed()
    {
        $user = User::factory()->create();
        $lab = Lab::create([
            'cml_id' => 'lab-uuid-3', 
            'lab_title' => 'Paid Lab OK',
            'price_cents' => 50000,
            'state' => 'STOPPED',
            'node_count' => 1,
            'created' => now(),
            'modified' => now(),
        ]);

        // Créer une réservation "pending" AVEC paiement
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'pending', // Reste pending tant que le lab n'est pas démarré
            'estimated_cents' => 50000
        ]);

        // Créer le paiement validé
        $reservation->payments()->create([
            'user_id' => $user->id,
            'amount' => 50000,
            'currency' => 'XOF',
            'status' => 'completed',
            'transaction_id' => 'TRANS_123',
            'description' => 'Payment',
            'customer_name' => $user->name,
            'customer_surname' => 'Doe',
            'customer_email' => $user->email,
            'customer_phone_number' => '0000000000',
        ]);

        // Simuler le token CML en session
        session(['cml_token' => 'fake-token']);

        // 1. Tenter de démarrer le lab via l'API
        // Note: Le mock CiscoApiService simulera le succès
        $responseApi = $this->actingAs($user)->postJson("/api/labs/{$lab->id}/start");
        
        // Doit réussir ou au moins passer la vérification de sécurité
        // Si ça échoue, ce sera pour d'autres raisons (mock incomplet), mais pas 403
        $responseApi->assertStatus(200);
    }
}
