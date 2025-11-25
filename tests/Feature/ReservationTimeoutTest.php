<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\User;
use App\Models\Reservation;
use App\Services\CinetPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

use App\Services\CiscoApiService;

class ReservationTimeoutTest extends TestCase
{
    // Utiliser RefreshDatabase pour avoir une base propre à chaque test
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mocker CiscoApiService pour éviter les appels réels et erreurs de sécurité
        $this->mock(CiscoApiService::class, function ($mock) {
            $mock->shouldReceive('setToken')->andReturnNull();
            $mock->shouldReceive('getLabState')->andReturn(['state' => 'RUNNING']);
            $mock->shouldReceive('getLabsAnnotation')->andReturn([]);
        });
        
        // Créer un utilisateur manuellement
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        // Créer un lab manuellement
        $this->lab = Lab::create([
            'cml_id' => 'test-lab-id-123',
            'title' => 'Test Lab',
            'lab_title' => 'Test Lab',
            'description' => 'A test lab',
            'state' => 'RUNNING',
            'price_cents' => 1000,
            'currency' => 'XOF',
        ]);
    }

    public function test_reservation_is_created_and_returns_201_on_payment_timeout()
    {
        // 1. Mocker le service CinetPay pour simuler un timeout
        $mockCinetPay = Mockery::mock(CinetPayService::class);
        $mockCinetPay->shouldReceive('initiatePayment')
            ->once()
            ->andThrow(new \Exception('cURL error 28: Operation timed out after 1000 milliseconds with 0 bytes received'));

        // Remplacer le service réel par le mock dans le conteneur de services
        $this->app->instance(CinetPayService::class, $mockCinetPay);

        // 2. Simuler une requête de réservation authentifiée
        $response = $this->actingAs($this->user)
            ->postJson('/api/labs/reserve', [
                'lab_id' => $this->lab->cml_id,
                'start_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'end_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'is_instant' => false,
            ]);

        // 3. Vérifications

        // Le code de statut doit être 201 (Created) et non 500
        $response->assertStatus(201);

        // La structure JSON doit contenir les indicateurs d'erreur de paiement
        $response->assertJsonStructure([
            'reservation',
            'requires_payment',
            'payment_error',
            'error',
            'code',
            'is_timeout',
            'can_retry_payment',
            'retry_payment_url',
            'message'
        ]);

        // Vérifier les valeurs spécifiques
        $response->assertJson([
            'requires_payment' => true,
            'payment_error' => true,
            'is_timeout' => true,
            'code' => 'TIMEOUT',
        ]);

        // Vérifier que la réservation a bien été créée en base de données
        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->user->id,
            'lab_id' => $this->lab->id,
            'status' => 'pending',
        ]);

        // Vérifier que les notes de la réservation mentionnent le timeout
        $reservation = Reservation::latest()->first();
        $this->assertStringContainsString('Timeout', $reservation->notes);
    }
}
