<?php

use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CiscoApiServiceTest extends TestCase
{
    public function test_auth_extended_success_stores_token()
    {
        Http::fake([
            '*/api/v0/auth_extended' => Http::response(['token' => 'abc123'], 200),
        ]);

        $service = new CiscoApiService();
        $res = $service->auth_extended('user', 'pass');

        $this->assertIsArray($res);
        $this->assertArrayHasKey('token', $res);
        $this->assertEquals('abc123', $res['token']);
    }

    public function test_auth_extended_failure_returns_error()
    {
        Http::fake([
            '*/api/v0/auth_extended' => Http::response([], 403),
        ]);

        $service = new CiscoApiService();
        $res = $service->auth_extended('user', 'bad');

        $this->assertIsArray($res);
        $this->assertArrayHasKey('error', $res);
    }
}


