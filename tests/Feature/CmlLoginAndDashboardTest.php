<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmlLoginAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_dashboard_shows_labs()
    {
        Http::fake([
            '*/api/v0/auth_extended' => Http::response(['token' => 'testtoken'], 200),
            '*/api/v0/labs' => Http::response(['90f84e38-a71c-4d57-8d90-00fa8a197385'], 200),
            '*/api/v0/labs/*' => Http::response(['id' => '90f84e38-a71c-4d57-8d90-00fa8a197385', 'name' => 'Lab One', 'description' => 'Test lab'], 200),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('testtoken', session('cml_token'));

        // Visit dashboard
        $dashboard = $this->get('/dashboard');
        $dashboard->assertStatus(200);

        // Ensure local user was created
        $this->assertDatabaseHas('users', ['name' => 'testuser']);
    }
}



