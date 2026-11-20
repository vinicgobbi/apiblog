<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_preflight_from_front_origin_is_allowed(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:4200',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/auth/login');

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4200');
    }

    public function test_request_from_unknown_origin_is_not_allowed(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://site-malicioso.example',
        ])->getJson('/api');

        $response->assertOk();
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
